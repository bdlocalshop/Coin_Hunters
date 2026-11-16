<?php

declare(strict_types=1);

namespace App\Domain\Payouts\Services;

use App\Domain\Ledger\Exceptions\InsufficientBalanceException;
use App\Domain\Ledger\Models\LedgerTransaction;
use App\Domain\Ledger\Services\LedgerService;
use App\Domain\Payouts\Models\CryptoPayoutMethod;
use App\Domain\Payouts\Models\WithdrawalRequest;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class PayoutService
{
    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * User submits a cryptocurrency withdrawal request.
     *
     * @throws TooManyRequestsHttpException
     * @throws InvalidArgumentException
     * @throws InsufficientBalanceException
     */
    public function requestWithdrawal(
        User $user,
        int $methodId,
        int $coins,
        string $walletAddress,
    ): WithdrawalRequest {
        // 1. Rate Limiting: Max 3 withdrawal requests per hour per user
        $rateLimitKey = "withdrawal_limit_{$user->id}";
        try {
            if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
                $seconds = RateLimiter::availableIn($rateLimitKey);
                throw new TooManyRequestsHttpException($seconds, "Too many withdrawal requests. Please wait {$seconds} seconds.");
            }
        } catch (\Throwable $e) {
            if ($e instanceof TooManyRequestsHttpException) {
                throw $e;
            }
            // Fallback DB count check if Redis rate limiter is offline
            $recentCount = WithdrawalRequest::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subHour())
                ->count();
            if ($recentCount >= 3) {
                throw new TooManyRequestsHttpException(3600, 'Too many withdrawal requests in the past hour (maximum 3 allowed).');
            }
        }

        // 2. Resolve and validate active crypto method
        $method = CryptoPayoutMethod::where('id', $methodId)->where('is_active', true)->first();
        if ($method === null) {
            throw new InvalidArgumentException('Selected cryptocurrency payout method is unavailable or inactive.');
        }

        // 3. Minimum coins validation
        if ($coins < $method->min_coins) {
            throw new InvalidArgumentException(
                sprintf('Minimum withdrawal threshold for %s is %d Coins ($%s USD).', $method->name, $method->min_coins, number_format($method->min_coins / 1000, 2))
            );
        }

        // 4. Wallet address format regex validation
        $walletAddress = trim($walletAddress);
        if (!empty($method->address_regex)) {
            $regex = '/' . trim($method->address_regex, '/') . '/';
            if (!preg_match($regex, $walletAddress)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid %s wallet destination address format.', $method->name)
                );
            }
        }

        // 5. Atomic check & ledger debit into withdrawal escrow
        return DB::transaction(function () use ($user, $method, $coins, $walletAddress, $rateLimitKey): WithdrawalRequest {
            $user->refresh();

            if ($user->available_coins < $coins) {
                throw new InsufficientBalanceException(
                    sprintf('Insufficient available coins. Balance: %d, Requested: %d.', $user->available_coins, $coins)
                );
            }

            $uuid = (string) Str::uuid();
            $idempotencyKey = "wd_lock_{$uuid}";

            // Immediate Ledger debit: available_coins -> locked_withdrawal_coins
            $this->ledgerService->lockWithdrawal(
                user: $user,
                coins: $coins,
                idempotencyKey: $idempotencyKey,
                withdrawalId: $uuid,
                cryptoAsset: $method->symbol
            );

            // Record withdrawal request
            $usdAmount = round($coins / 1000, 2);

            $withdrawal = WithdrawalRequest::create([
                'uuid' => $uuid,
                'user_id' => $user->id,
                'crypto_payout_method_id' => $method->id,
                'coins_amount' => $coins,
                'usd_amount' => $usdAmount,
                'wallet_address' => $walletAddress,
                'status' => 'pending',
            ]);

            try {
                RateLimiter::hit($rateLimitKey, 3600);
            } catch (\Throwable) {
                // Redis offline fallback
            }

            return $withdrawal->load(['payoutMethod', 'user']);
        });
    }

    /**
     * Admin approves payout and records blockchain transaction hash.
     */
    public function approveWithdrawal(
        WithdrawalRequest $withdrawal,
        User $admin,
        string $txHash,
    ): WithdrawalRequest {
        $txHash = trim($txHash);
        if (empty($txHash)) {
            throw new InvalidArgumentException('A valid on-chain blockchain transaction hash (tx_hash) is required to approve payout.');
        }

        if ($withdrawal->status !== 'pending') {
            throw new DomainException("Withdrawal request [{$withdrawal->id}] is already {$withdrawal->status} and cannot be approved.");
        }

        return DB::transaction(function () use ($withdrawal, $admin, $txHash): WithdrawalRequest {
            $user = $withdrawal->user;
            $parentTx = LedgerTransaction::where('reference_id', $withdrawal->uuid)->first();

            $idempotencyKey = "wd_approve_{$withdrawal->uuid}";

            // Ledger finalize: locked_withdrawal_coins -> asset:crypto_treasury
            $this->ledgerService->finalizeWithdrawal(
                user: $user,
                coins: $withdrawal->coins_amount,
                idempotencyKey: $idempotencyKey,
                withdrawalId: $withdrawal->uuid,
                txHash: $txHash,
                parentTransactionId: $parentTx?->id
            );

            $withdrawal->status = 'approved';
            $withdrawal->tx_hash = $txHash;
            $withdrawal->reviewed_by_admin_id = $admin->id;
            $withdrawal->reviewed_at = now();
            $withdrawal->save();

            return $withdrawal->fresh(['payoutMethod', 'user', 'reviewedByAdmin']);
        });
    }

    /**
     * Admin rejects payout with reason and refunds locked coins to user balance.
     */
    public function rejectWithdrawal(
        WithdrawalRequest $withdrawal,
        User $admin,
        string $reason,
    ): WithdrawalRequest {
        $reason = trim($reason);
        if (empty($reason)) {
            throw new InvalidArgumentException('A non-empty rejection reason must be provided to refund coins to the user.');
        }

        if ($withdrawal->status !== 'pending') {
            throw new DomainException("Withdrawal request [{$withdrawal->id}] is already {$withdrawal->status} and cannot be rejected.");
        }

        return DB::transaction(function () use ($withdrawal, $admin, $reason): WithdrawalRequest {
            $user = $withdrawal->user;
            $parentTx = LedgerTransaction::where('reference_id', $withdrawal->uuid)->first();

            $idempotencyKey = "wd_refund_{$withdrawal->uuid}";

            // Ledger reversal: locked_withdrawal_coins -> available_coins
            $this->ledgerService->refundWithdrawal(
                user: $user,
                coins: $withdrawal->coins_amount,
                idempotencyKey: $idempotencyKey,
                withdrawalId: $withdrawal->uuid,
                reason: $reason,
                parentTransactionId: $parentTx?->id
            );

            $withdrawal->status = 'rejected';
            $withdrawal->rejection_reason = $reason;
            $withdrawal->reviewed_by_admin_id = $admin->id;
            $withdrawal->reviewed_at = now();
            $withdrawal->save();

            return $withdrawal->fresh(['payoutMethod', 'user', 'reviewedByAdmin']);
        });
    }
}
