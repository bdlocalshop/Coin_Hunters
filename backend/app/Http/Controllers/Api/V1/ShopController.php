<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Payouts\Models\CryptoPayoutMethod;
use App\Domain\Payouts\Models\WithdrawalRequest;
use App\Domain\Payouts\Services\PayoutService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(
        private readonly PayoutService $payoutService
    ) {}

    /**
     * Get active cryptocurrency cashout redemption methods.
     */
    public function methods(): JsonResponse
    {
        $methods = CryptoPayoutMethod::where('is_active', true)->get()->map(function (CryptoPayoutMethod $m) {
            return [
                'id' => $m->id,
                'symbol' => $m->symbol,
                'name' => $m->name,
                'network' => $m->network,
                'icon_url' => $m->icon_url,
                'min_coins' => $m->min_coins,
                'fee_coins' => $m->fee_coins,
                'min_usd' => number_format($m->min_coins / 1000, 2),
                'address_regex' => $m->address_regex,
            ];
        });

        return response()->json(['methods' => $methods]);
    }

    /**
     * Submit a new crypto withdrawal cashout request.
     */
    public function withdraw(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'crypto_payout_method_id' => ['required', 'integer', 'exists:crypto_payout_methods,id'],
            'coins' => ['required', 'integer', 'min:100'],
            'wallet_address' => ['required', 'string', 'max:255'],
        ]);

        try {
            $withdrawal = $this->payoutService->requestWithdrawal(
                user: $request->user(),
                methodId: (int) $validated['crypto_payout_method_id'],
                coins: (int) $validated['coins'],
                walletAddress: (string) $validated['wallet_address'],
            );

            return response()->json([
                'message' => 'Withdrawal request submitted successfully and queued for review.',
                'withdrawal' => [
                    'id' => $withdrawal->id,
                    'uuid' => $withdrawal->uuid,
                    'symbol' => $withdrawal->payoutMethod->symbol,
                    'network' => $withdrawal->payoutMethod->network,
                    'coins_amount' => $withdrawal->coins_amount,
                    'usd_amount' => $withdrawal->usd_amount,
                    'wallet_address' => $withdrawal->wallet_address,
                    'status' => $withdrawal->status,
                    'created_at' => $withdrawal->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get user's past and pending withdrawal requests.
     */
    public function withdrawals(Request $request): JsonResponse
    {
        $withdrawals = WithdrawalRequest::where('user_id', $request->user()->id)
            ->with('payoutMethod:id,symbol,name,network,icon_url')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($withdrawals);
    }
}
