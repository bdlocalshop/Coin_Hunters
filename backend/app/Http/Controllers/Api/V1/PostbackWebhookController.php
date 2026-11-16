<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Fraud\Models\PlatformSetting;
use App\Domain\Ledger\Models\LedgerTransaction;
use App\Domain\Ledger\Services\LedgerService;
use App\Domain\Offerwalls\Models\Offer;
use App\Domain\Offerwalls\Models\OfferClick;
use App\Domain\Offerwalls\Models\OfferHold;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Domain\Offerwalls\Models\PostbackLog;
use App\Domain\Offerwalls\Services\OfferwallRegistry;
use App\Domain\Referrals\Services\ReferralService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PostbackWebhookController extends Controller
{
    public function __construct(
        private readonly OfferwallRegistry $registry,
        private readonly LedgerService $ledgerService,
        private readonly ReferralService $referralService
    ) {}

    /**
     * S2S Webhook entry point with 3-Gate Security Pipeline.
     */
    public function handle(Request $request, string $provider): JsonResponse|Response
    {
        /** @var OfferwallProvider|null $providerModel */
        $providerModel = $request->attributes->get('offerwall_provider')
            ?? OfferwallProvider::where('slug', $provider)->where('is_active', true)->first();

        if ($providerModel === null) {
            return response()->json(['status' => 'error', 'message' => 'Provider not found or inactive.'], 404);
        }

        if (!$this->registry->hasAdapter($providerModel->slug)) {
            return response()->json(['status' => 'error', 'message' => 'Adapter not available.'], 404);
        }

        $adapter = $this->registry->getAdapter($providerModel->slug);
        $clientIp = (string) ($request->header('CF-Connecting-IP') ?? $request->ip() ?? '127.0.0.1');

        // GATE 2: Cryptographic Signature / Secret Verification
        if (!$adapter->verifySignature($request, $providerModel)) {
            PostbackLog::create([
                'provider_slug' => $providerModel->slug,
                'external_transaction_id' => (string) ($request->input('transaction_id') ?? $request->input('tx') ?? $request->input('trans_id')),
                'ip_address' => $clientIp,
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
                'status' => 'rejected_sig',
                'message' => 'Cryptographic signature or secret token mismatch.',
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invalid signature.'], Response::HTTP_UNAUTHORIZED);
        }

        // Parse normalized payload
        $dto = $adapter->parsePostback($request, $providerModel);
        $idempotencyKey = sprintf('postback_%s_%s', $providerModel->slug, $dto->externalTransactionId);

        // GATE 3: Database Idempotency Lock
        $existingLog = PostbackLog::where('provider_slug', $providerModel->slug)
            ->where('external_transaction_id', $dto->externalTransactionId)
            ->whereIn('status', ['credited', 'held'])
            ->first();

        $existingTx = LedgerTransaction::where('idempotency_key', $idempotencyKey)->first();

        if ($existingLog !== null || $existingTx !== null) {
            // Already processed — log idempotency event and return 200 OK immediately
            PostbackLog::create([
                'provider_slug' => $providerModel->slug,
                'external_transaction_id' => $dto->externalTransactionId,
                'ip_address' => $clientIp,
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
                'status' => 'duplicate',
                'message' => 'Duplicate transaction received. Idempotency lock active.',
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Duplicate postback received. Handled idempotently.',
            ], Response::HTTP_OK);
        }

        // Locate user by user_id or click_uuid
        $user = User::find($dto->userId);
        $click = null;

        if ($dto->clickUuid) {
            $click = OfferClick::where('click_uuid', $dto->clickUuid)->first();
            if ($user === null && $click !== null) {
                $user = $click->user;
            }
        }

        if ($user === null) {
            PostbackLog::create([
                'provider_slug' => $providerModel->slug,
                'external_transaction_id' => $dto->externalTransactionId,
                'ip_address' => $clientIp,
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
                'status' => 'error',
                'message' => "User ID [{$dto->userId}] not found.",
            ]);

            return response()->json(['status' => 'error', 'message' => 'User not found.'], Response::HTTP_BAD_REQUEST);
        }

        // Handle Reversals / Chargebacks
        if ($dto->isReversal) {
            $parentTx = LedgerTransaction::where('reference_id', $dto->externalTransactionId)->first();

            $this->ledgerService->chargeback(
                user: $user,
                coins: $dto->payoutCoins,
                idempotencyKey: $idempotencyKey . '_reversal',
                parentTransactionId: $parentTx?->id,
                reason: $dto->reversalReason ?? 'Advertiser reversal postback',
                fromPending: false
            );

            if ($click) {
                $click->status = 'reversed';
                $click->save();
            }

            PostbackLog::create([
                'provider_slug' => $providerModel->slug,
                'external_transaction_id' => $dto->externalTransactionId,
                'ip_address' => $clientIp,
                'headers' => $request->headers->all(),
                'payload' => $request->all(),
                'status' => 'reversed',
                'message' => 'Compensating chargeback reversal posted.',
            ]);

            return response()->json(['status' => 'ok', 'result' => 'reversed'], Response::HTTP_OK);
        }

        // Check High-Value Hold Policy
        $holdThreshold = PlatformSetting::getInt('hold_threshold_coins', 5000);
        $isHighValueHold = $dto->payoutCoins >= $holdThreshold;

        // Post to Double-Entry Ledger
        $tx = $this->ledgerService->creditOfferEarning(
            user: $user,
            coins: $dto->payoutCoins,
            offerId: $dto->externalTransactionId,
            idempotencyKey: $idempotencyKey,
            provider: $providerModel->slug,
            hold: $isHighValueHold,
            metadata: [
                'external_transaction_id' => $dto->externalTransactionId,
                'network_payout_usd' => $dto->payoutUsd,
                'click_uuid' => $dto->clickUuid,
                'offer_id' => $dto->offerId,
            ]
        );

        $offer = Offer::where('provider_slug', $providerModel->slug)
            ->where('external_offer_id', $dto->offerId)
            ->first();

        if ($isHighValueHold) {
            $holdDays = PlatformSetting::getInt('hold_duration_days', 14);
            $releaseAt = now()->addDays($holdDays);

            OfferHold::create([
                'user_id' => $user->id,
                'ledger_transaction_id' => $tx->id,
                'offer_id' => $offer?->id,
                'provider_slug' => $providerModel->slug,
                'external_transaction_id' => $dto->externalTransactionId,
                'coins' => $dto->payoutCoins,
                'release_at' => $releaseAt,
                'status' => 'pending',
            ]);

            $logStatus = 'held';
            $logMessage = "Credited to pending balance (high value hold: {$holdDays} days).";
        } else {
            $logStatus = 'credited';
            $logMessage = 'Credited immediately to user available coins.';
        }

        // Trigger Flat Referral Qualification Check
        $user->refresh();
        $this->referralService->checkAndRewardQualification($user);

        // Update tracking click status
        if ($click) {
            $click->status = $isHighValueHold ? 'pending' : 'completed';
            $click->save();
        }

        // Log to postback_logs audit table
        PostbackLog::create([
            'provider_slug' => $providerModel->slug,
            'external_transaction_id' => $dto->externalTransactionId,
            'ip_address' => $clientIp,
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'status' => $logStatus,
            'message' => $logMessage,
        ]);

        return response()->json(['status' => 'ok', 'result' => 1], Response::HTTP_OK);
    }
}
