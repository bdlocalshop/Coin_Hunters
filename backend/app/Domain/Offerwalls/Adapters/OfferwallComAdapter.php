<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Adapters;

use App\Domain\Offerwalls\DTOs\PostbackPayloadDTO;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Models\User;
use Illuminate\Http\Request;

class OfferwallComAdapter implements OfferwallAdapterInterface
{
    public function getProviderSlug(): string
    {
        return 'offerwall_com';
    }

    public function fetchCampaigns(OfferwallProvider $provider, array $filters = []): array
    {
        return [
            [
                'external_offer_id' => 'owcom_chime_bank',
                'title' => 'Chime — Online Banking',
                'description' => 'Open a new Chime checking account and receive a qualifying direct deposit of $200+ within 45 days.',
                'requirements' => ['Sign up for Chime', 'Receive $200+ direct deposit', 'US residents only'],
                'icon_url' => 'https://images.unsplash.com/photo-1563986768609-322da13575f3?w=200&auto=format&fit=crop&q=80',
                'network_payout_usd' => 120.00,
                'user_payout_coins' => (int) round(120.00 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'device_targets' => ['android', 'ios', 'desktop'],
                'countries' => ['US'],
                'categories' => ['finance', 'apps'],
                'click_url_template' => 'https://offerwall.com/track?api_key={api_key}&subid={click_uuid}&user={user_id}',
                'epc_score' => 3.2000,
                'is_featured' => true,
            ],
            [
                'external_offer_id' => 'owcom_crypto_com',
                'title' => 'Crypto.com App',
                'description' => 'Download the app, complete KYC Level 2, and purchase at least $10 worth of cryptocurrency.',
                'requirements' => ['Complete KYC Level 2', 'First crypto trade >= $10'],
                'icon_url' => 'https://images.unsplash.com/photo-1621416894569-0f39ed31d247?w=200&auto=format&fit=crop&q=80',
                'network_payout_usd' => 35.00,
                'user_payout_coins' => (int) round(35.00 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'device_targets' => ['android', 'ios'],
                'countries' => ['US', 'CA', 'GB', 'DE', 'AU'],
                'categories' => ['crypto', 'finance'],
                'click_url_template' => 'https://offerwall.com/track?api_key={api_key}&subid={click_uuid}&user={user_id}',
                'epc_score' => 1.1500,
                'is_featured' => false,
            ],
        ];
    }

    public function fetchSurveys(OfferwallProvider $provider, User $user, array $params = []): array
    {
        return [];
    }

    public function verifySignature(Request $request, OfferwallProvider $provider): bool
    {
        $secret = $provider->secret_key ?? '';
        $token = (string) ($request->input('secret') ?? $request->header('X-Secret-Token') ?? '');

        if (!empty($token) && hash_equals($secret, $token)) {
            return true;
        }

        $sig = (string) ($request->input('sig') ?? $request->header('X-Signature') ?? '');
        $txId = (string) $request->input('trans_id', $request->input('transaction_id'));
        $userId = (string) $request->input('user_id');

        $expectedHmac = hash_hmac('sha256', $txId . ':' . $userId, $secret);
        return hash_equals($expectedHmac, $sig);
    }

    public function parsePostback(Request $request, OfferwallProvider $provider): PostbackPayloadDTO
    {
        $txId = (string) ($request->input('trans_id') ?? $request->input('transaction_id') ?? uniqid('tx_com_'));
        $userId = (int) $request->input('user_id');
        $clickUuid = $request->input('subid') ?? $request->input('click_uuid');
        $offerId = (string) $request->input('offer_id', 'unknown');

        $payoutUsd = (float) $request->input('payout', 0.0);
        $coinsInput = $request->input('coins');
        $isReversal = (int) $request->input('status', 1) === 0 || $payoutUsd < 0;

        if ($coinsInput !== null) {
            $payoutCoins = abs((int) $coinsInput);
        } else {
            $payoutCoins = (int) round(abs($payoutUsd) * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier);
        }

        return new PostbackPayloadDTO(
            providerSlug: $this->getProviderSlug(),
            externalTransactionId: $txId,
            userId: $userId,
            payoutCoins: $payoutCoins,
            payoutUsd: abs($payoutUsd),
            isReversal: $isReversal,
            clickUuid: $clickUuid,
            offerId: $offerId,
            reversalReason: $isReversal ? 'Offerwall.com reversal' : null,
            rawPayload: $request->all(),
        );
    }
}
