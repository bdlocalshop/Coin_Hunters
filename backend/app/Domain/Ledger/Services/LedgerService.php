<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Services;

use App\Domain\Ledger\DTOs\LedgerEntryDTO;
use App\Domain\Ledger\DTOs\PostTransactionDTO;
use App\Domain\Ledger\Enums\AccountCode;
use App\Domain\Ledger\Enums\EntryDirection;
use App\Domain\Ledger\Enums\TransactionType;
use App\Domain\Ledger\Exceptions\DuplicateTransactionException;
use App\Domain\Ledger\Exceptions\InsufficientBalanceException;
use App\Domain\Ledger\Exceptions\InvalidLedgerAccountException;
use App\Domain\Ledger\Exceptions\UnbalancedLedgerException;
use App\Domain\Ledger\Models\LedgerAccount;
use App\Domain\Ledger\Models\LedgerEntry;
use App\Domain\Ledger\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LedgerService
{
    /**
     * Post a balanced double-entry transaction and atomically update user projections.
     *
     * @throws UnbalancedLedgerException
     * @throws DuplicateTransactionException
     * @throws InsufficientBalanceException
     * @throws InvalidLedgerAccountException
     */
    public function post(PostTransactionDTO $data): LedgerTransaction
    {
        // 1. Minimum entries check
        if (count($data->entries) < 2) {
            throw new UnbalancedLedgerException('A double-entry transaction requires at least two balanced entries.');
        }

        // 2. Amount > 0 check and Zero-Sum Double-Entry verification
        $totalDebits = 0;
        $totalCredits = 0;

        foreach ($data->entries as $entry) {
            if ($entry->amount <= 0) {
                throw new UnbalancedLedgerException("Ledger entry amount must be greater than zero. Received: {$entry->amount}.");
            }

            if ($entry->direction === EntryDirection::DEBIT) {
                $totalDebits += $entry->amount;
            } else {
                $totalCredits += $entry->amount;
            }
        }

        if ($totalDebits !== $totalCredits) {
            throw new UnbalancedLedgerException(
                "Unbalanced ledger transaction: sum of debits ({$totalDebits}) does not match sum of credits ({$totalCredits})."
            );
        }

        // 3. Execute inside atomic database transaction
        return DB::transaction(function () use ($data): LedgerTransaction {
            // Check idempotency lock
            $existing = LedgerTransaction::where('idempotency_key', $data->idempotencyKey)->first();
            if ($existing !== null) {
                throw new DuplicateTransactionException("Transaction with idempotency key [{$data->idempotencyKey}] already exists.");
            }

            // Resolve and cache required accounts
            $accountCodes = array_unique(array_map(fn (LedgerEntryDTO $e) => $e->accountCode, $data->entries));
            $accounts = LedgerAccount::whereIn('code', $accountCodes)->get()->keyBy('code');

            foreach ($accountCodes as $code) {
                if (!isset($accounts[$code])) {
                    throw new InvalidLedgerAccountException("Ledger account with code [{$code}] does not exist in chart of accounts.");
                }
            }

            // Collect unique user IDs involved in this transaction
            $userIds = array_values(array_filter(
                array_unique(array_map(fn (LedgerEntryDTO $e) => $e->userId, $data->entries)),
                fn (?int $id) => $id !== null
            ));

            // Lock affected user rows for update (pessimistic row-level lock)
            /** @var array<int, User> $lockedUsers */
            $lockedUsers = [];
            if (!empty($userIds)) {
                $users = User::whereIn('id', $userIds)->lockForUpdate()->get();
                foreach ($users as $user) {
                    $lockedUsers[$user->id] = $user;
                }

                foreach ($userIds as $id) {
                    if (!isset($lockedUsers[$id])) {
                        throw new InsufficientBalanceException("User ID [{$id}] referenced in ledger entry was not found.");
                    }
                }
            }

            // Determine if transaction is earning
            $isEarning = false;
            try {
                $transType = TransactionType::from($data->type);
                $isEarning = $transType->isEarning();
            } catch (\ValueError) {
                $isEarning = false;
            }

            // 4. Update materialized projection balances for users
            foreach ($data->entries as $entry) {
                if ($entry->userId === null) {
                    continue;
                }

                $user = $lockedUsers[$entry->userId];

                switch ($entry->accountCode) {
                    case AccountCode::LIABILITY_USER_AVAILABLE_BALANCE->value:
                        if ($entry->direction === EntryDirection::CREDIT) {
                            $user->available_coins += $entry->amount;
                            if ($isEarning) {
                                $user->lifetime_earned_coins += $entry->amount;
                            }
                        } else { // DEBIT
                            if ($user->available_coins < $entry->amount) {
                                throw new InsufficientBalanceException(
                                    "User [{$user->id}] available balance ({$user->available_coins}) is insufficient for debit of ({$entry->amount}) coins."
                                );
                            }
                            $user->available_coins -= $entry->amount;
                        }
                        break;

                    case AccountCode::LIABILITY_USER_PENDING_BALANCE->value:
                        if ($entry->direction === EntryDirection::CREDIT) {
                            $user->pending_coins += $entry->amount;
                            if ($isEarning) {
                                $user->lifetime_earned_coins += $entry->amount;
                            }
                        } else { // DEBIT
                            if ($user->pending_coins < $entry->amount) {
                                throw new InsufficientBalanceException(
                                    "User [{$user->id}] pending balance ({$user->pending_coins}) is insufficient for debit of ({$entry->amount}) coins."
                                );
                            }
                            $user->pending_coins -= $entry->amount;
                        }
                        break;

                    case AccountCode::LIABILITY_USER_LOCKED_WITHDRAWAL->value:
                        if ($entry->direction === EntryDirection::CREDIT) {
                            $user->locked_withdrawal_coins += $entry->amount;
                        } else { // DEBIT
                            if ($user->locked_withdrawal_coins < $entry->amount) {
                                throw new InsufficientBalanceException(
                                    "User [{$user->id}] locked withdrawal balance ({$user->locked_withdrawal_coins}) is insufficient for debit of ({$entry->amount}) coins."
                                );
                            }
                            $user->locked_withdrawal_coins -= $entry->amount;
                        }
                        break;
                }
            }

            // Save user projection updates
            foreach ($lockedUsers as $user) {
                $user->save();
            }

            // 5. Create immutable header transaction record
            $now = now();
            $transaction = new LedgerTransaction();
            $transaction->uuid = (string) Str::uuid();
            $transaction->parent_transaction_id = $data->parentTransactionId;
            $transaction->type = $data->type;
            $transaction->reference_type = $data->referenceType;
            $transaction->reference_id = $data->referenceId;
            $transaction->idempotency_key = $data->idempotencyKey;
            $transaction->description = $data->description;
            $transaction->metadata = $data->metadata;
            $transaction->created_at = $now;
            $transaction->saveQuietly();

            // 6. Create immutable entry records
            foreach ($data->entries as $entry) {
                $account = $accounts[$entry->accountCode];

                $entryRecord = new LedgerEntry();
                $entryRecord->ledger_transaction_id = $transaction->id;
                $entryRecord->ledger_account_id = $account->id;
                $entryRecord->user_id = $entry->userId;
                $entryRecord->direction = $entry->direction;
                $entryRecord->amount = $entry->amount;
                $entryRecord->created_at = $now;
                $entryRecord->saveQuietly();
            }

            return $transaction->load('entries.account');
        });
    }

    /**
     * Find transaction by idempotency key.
     */
    public function findByIdempotencyKey(string $key): ?LedgerTransaction
    {
        return LedgerTransaction::where('idempotency_key', $key)->with('entries.account')->first();
    }

    /**
     * Credit immediate user earnings from an offerwall or survey completion.
     */
    public function creditOfferEarning(
        User $user,
        int $coins,
        string $offerId,
        string $idempotencyKey,
        ?string $provider = null,
        bool $hold = false,
        ?array $metadata = null,
    ): LedgerTransaction {
        $liabilityAccount = $hold
            ? AccountCode::LIABILITY_USER_PENDING_BALANCE
            : AccountCode::LIABILITY_USER_AVAILABLE_BALANCE;

        $type = $hold ? TransactionType::OFFER_PENDING : TransactionType::OFFER_COMPLETION;
        $description = sprintf('Offer completion reward (%s): %d Coins', $offerId, $coins);

        $dto = new PostTransactionDTO(
            type: $type,
            description: $description,
            idempotencyKey: $idempotencyKey,
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::ASSET_NETWORK_RECEIVABLES,
                    direction: EntryDirection::DEBIT,
                    amount: $coins,
                ),
                new LedgerEntryDTO(
                    account: $liabilityAccount,
                    direction: EntryDirection::CREDIT,
                    amount: $coins,
                    userId: $user->id,
                ),
            ],
            referenceType: 'offer',
            referenceId: $offerId,
            metadata: array_merge(['provider' => $provider, 'held' => $hold], $metadata ?? []),
        );

        return $this->post($dto);
    }

    /**
     * Release held pending coins into spendable available balance.
     */
    public function releasePendingCoins(
        User $user,
        int $coins,
        string $idempotencyKey,
        ?int $parentTransactionId = null,
        ?string $description = null,
    ): LedgerTransaction {
        $dto = new PostTransactionDTO(
            type: TransactionType::PENDING_RELEASE,
            description: $description ?? sprintf('Release held pending balance of %d Coins to available', $coins),
            idempotencyKey: $idempotencyKey,
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_PENDING_BALANCE,
                    direction: EntryDirection::DEBIT,
                    amount: $coins,
                    userId: $user->id,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_AVAILABLE_BALANCE,
                    direction: EntryDirection::CREDIT,
                    amount: $coins,
                    userId: $user->id,
                ),
            ],
            parentTransactionId: $parentTransactionId,
        );

        return $this->post($dto);
    }

    /**
     * Credit promotional bonuses (signup bonus, flat referral bonus).
     */
    public function creditBonus(
        User $user,
        int $coins,
        TransactionType $type,
        string $idempotencyKey,
        string $description,
        ?array $metadata = null,
    ): LedgerTransaction {
        $dto = new PostTransactionDTO(
            type: $type,
            description: $description,
            idempotencyKey: $idempotencyKey,
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::EXPENSE_MARKETING_BONUSES,
                    direction: EntryDirection::DEBIT,
                    amount: $coins,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_AVAILABLE_BALANCE,
                    direction: EntryDirection::CREDIT,
                    amount: $coins,
                    userId: $user->id,
                ),
            ],
            metadata: $metadata,
        );

        return $this->post($dto);
    }

    /**
     * Lock user available coins into withdrawal escrow.
     */
    public function lockWithdrawal(
        User $user,
        int $coins,
        string $idempotencyKey,
        string $withdrawalId,
        ?string $cryptoAsset = null,
    ): LedgerTransaction {
        $dto = new PostTransactionDTO(
            type: TransactionType::WITHDRAWAL_REQUEST,
            description: sprintf('Withdrawal request [%s]: lock %d Coins', $withdrawalId, $coins),
            idempotencyKey: $idempotencyKey,
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_AVAILABLE_BALANCE,
                    direction: EntryDirection::DEBIT,
                    amount: $coins,
                    userId: $user->id,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_LOCKED_WITHDRAWAL,
                    direction: EntryDirection::CREDIT,
                    amount: $coins,
                    userId: $user->id,
                ),
            ],
            referenceType: 'withdrawal',
            referenceId: $withdrawalId,
            metadata: ['asset' => $cryptoAsset],
        );

        return $this->post($dto);
    }

    /**
     * Finalize an approved crypto withdrawal (admin sets blockchain tx_hash).
     */
    public function finalizeWithdrawal(
        User $user,
        int $coins,
        string $idempotencyKey,
        string $withdrawalId,
        string $txHash,
        ?int $parentTransactionId = null,
    ): LedgerTransaction {
        $dto = new PostTransactionDTO(
            type: TransactionType::WITHDRAWAL_APPROVED,
            description: sprintf('Withdrawal [%s] paid on-chain: %d Coins (tx: %s)', $withdrawalId, $coins, $txHash),
            idempotencyKey: $idempotencyKey,
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_LOCKED_WITHDRAWAL,
                    direction: EntryDirection::DEBIT,
                    amount: $coins,
                    userId: $user->id,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::ASSET_CRYPTO_TREASURY,
                    direction: EntryDirection::CREDIT,
                    amount: $coins,
                ),
            ],
            referenceType: 'withdrawal',
            referenceId: $withdrawalId,
            parentTransactionId: $parentTransactionId,
            metadata: ['tx_hash' => $txHash],
        );

        return $this->post($dto);
    }

    /**
     * Reject and refund a withdrawal back to available balance.
     */
    public function refundWithdrawal(
        User $user,
        int $coins,
        string $idempotencyKey,
        string $withdrawalId,
        string $reason,
        ?int $parentTransactionId = null,
    ): LedgerTransaction {
        $dto = new PostTransactionDTO(
            type: TransactionType::WITHDRAWAL_REJECTED,
            description: sprintf('Withdrawal [%s] rejected: %s. Refund %d Coins to available.', $withdrawalId, $reason, $coins),
            idempotencyKey: $idempotencyKey,
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_LOCKED_WITHDRAWAL,
                    direction: EntryDirection::DEBIT,
                    amount: $coins,
                    userId: $user->id,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_AVAILABLE_BALANCE,
                    direction: EntryDirection::CREDIT,
                    amount: $coins,
                    userId: $user->id,
                ),
            ],
            referenceType: 'withdrawal',
            referenceId: $withdrawalId,
            parentTransactionId: $parentTransactionId,
            metadata: ['rejection_reason' => $reason],
        );

        return $this->post($dto);
    }

    /**
     * Post a chargeback reversal referencing parent transaction.
     */
    public function chargeback(
        User $user,
        int $coins,
        string $idempotencyKey,
        ?int $parentTransactionId = null,
        string $reason = 'Chargeback reversal',
        bool $fromPending = false,
    ): LedgerTransaction {
        $liabilityAccount = $fromPending
            ? AccountCode::LIABILITY_USER_PENDING_BALANCE
            : AccountCode::LIABILITY_USER_AVAILABLE_BALANCE;

        $dto = new PostTransactionDTO(
            type: TransactionType::CHARGEBACK,
            description: sprintf('Chargeback reversal: -%d Coins (%s)', $coins, $reason),
            idempotencyKey: $idempotencyKey,
            entries: [
                new LedgerEntryDTO(
                    account: $liabilityAccount,
                    direction: EntryDirection::DEBIT,
                    amount: $coins,
                    userId: $user->id,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::ASSET_NETWORK_RECEIVABLES,
                    direction: EntryDirection::CREDIT,
                    amount: $coins,
                ),
            ],
            parentTransactionId: $parentTransactionId,
            metadata: ['reason' => $reason],
        );

        return $this->post($dto);
    }
}
