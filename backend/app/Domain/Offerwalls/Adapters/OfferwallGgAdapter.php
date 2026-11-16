<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Adapters;

use App\Domain\Offerwalls\DTOs\PostbackPayloadDTO;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OfferwallGgAdapter implements OfferwallAdapterInterface
{
    public function getProviderSlug(): string
    {
        return 'offerwall_gg';
    }

    public function fetchCampaigns(OfferwallProvider $provider, array $filters = []): array
    {
        if (!empty($provider->api_key) && !str_starts_with($provider->api_key, 'test_')) {
            try {
                $response = Http::timeout(5)->get('https://api.offerwall.gg/v1/offers', [
                    'api_key' => $provider->api_key,
                    'app_id' => $provider->app_id,
                    'country' => $filters['country'] ?? 'US',
                    'platform' => $filters['platform'] ?? 'all',
                ]);

                if ($response->successful()) {
                    return $this->normalizeOffers($response->json('offers') ?? [], $provider);
                }
            } catch (\Throwable $e) {
                Log::warning('Offerwall.gg campaign sync failed: ' . $e->getMessage());
            }
        }

        // Default normalized mock campaign feed for local testing
        return [
            [
                'external_offer_id' => 'owgg_raid_shadow_legends',
                'title' => 'Raid: Shadow Legends',
                'description' => 'Install, open, and reach Player Level 40 within 30 days to earn Coins.',
                'requirements' => ['Install & Open', 'Reach Level 40 in 30 days', 'New users only'],
                'icon_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=200&auto=format&fit=crop&q=80',
                'network_payout_usd' => 45.00,
                'user_payout_coins' => (int) round(45.00 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'device_targets' => ['android', 'ios'],
                'countries' => ['US', 'CA', 'GB', 'DE', 'FR'],
                'categories' => ['games', 'rpg'],
                'click_url_template' => 'https://offerwall.gg/click?app_id={app_id}&offer_id=raid&subId={click_uuid}&user_id={user_id}',
                'epc_score' => 1.4500,
                'is_featured' => true,
            ],
            [
                'external_offer_id' => 'owgg_monopoly_go',
                'title' => 'Monopoly GO!',
                'description' => 'Roll the dice, build properties, and complete Board 26 within 21 days.',
                'requirements' => ['Install app', 'Reach Board 26 in 21 days'],
                'icon_url' => 'https://images.unsplash.com/photo-1612287232252-87063462908a?w=200&auto=format&fit=crop&q=80',
                'network_payout_usd' => 28.50,
                'user_payout_coins' => (int) round(28.50 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'device_targets' => ['android', 'ios'],
                'countries' => ['US', 'CA', 'GB', 'AU'],
                'categories' => ['games', 'casual'],
                'click_url_template' => 'https://offerwall.gg/click?app_id={app_id}&offer_id=monopoly&subId={click_uuid}&user_id={user_id}',
                'epc_score' => 0.9800,
                'is_featured' => true,
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
        $providedSig = (string) ($request->input('signature') ?? $request->header('X-Signature') ?? '');
        $providedSecret = (string) $request->input('secret');

        // Secret token check
        if (!empty($providedSecret) && hash_equals($secret, $providedSecret)) {
            return true;
        }

        // HMAC or MD5 checksum
        $txId = (string) $request->input('transaction_id', $request->input('tx_id'));
        $userId = (string) $request->input('user_id');
        $payout = (string) $request->input('payout', $request->input('amount'));

        $expectedMd5 = md5($secret . $userId . $payout . $txId);
        $expectedHmac = hash_hmac('sha256', $txId . ':' . $userId . ':' . $payout, $secret);

        return hash_equals($expectedMd5, $providedSig) || hash_equals($expectedHmac, $providedSig);
    }

    public function parsePostback(Request $request, OfferwallProvider $provider): PostbackPayloadDTO
    {
        $txId = (string) $request->input('transaction_id', $request->input('tx_id'));
        $userId = (int) $request->input('user_id');
        $clickUuid = $request->input('sub_id') ?? $request->input('subId') ?? $request->input('click_uuid');
        $offerId = (string) $request->input('offer_id', 'unknown');

        $payoutUsd = (float) $request->input('payout', 0.0);
        $coinsInput = $request->input('coins');

        $isReversal = (int) $request->input('status', 1) === 2 || $payoutUsd < 0;

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
            reversalReason: $isReversal ? (string) $request->input('reason', 'Advertiser reversal') : null,
            rawPayload: $request->all(),
        );
    }

    private function normalizeOffers(array $rawOffers, OfferwallProvider $provider): array
    {
        $normalized = [];
        foreach ($rawOffers as $o) {
            $netUsd = (float) ($o['payout'] ?? 0.0);
            $coins = (int) round($netUsd * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier);

            $normalized[] = [
                'external_offer_id' => (string) ($o['id'] ?? uniqid('owgg_')),
                'title' => (string) ($o['title'] ?? 'Sponsored Offer'),
                'description' => (string) ($o['description'] ?? ''),
                'requirements' => (array) ($o['instructions'] ?? []),
                'icon_url' => $o['icon_url'] ?? null,
                'network_payout_usd' => $netUsd,
                'user_payout_coins' => $coins,
                'device_targets' => (array) ($o['devices'] ?? ['android', 'ios']),
                'countries' => (array) ($o['countries'] ?? ['ALL']),
                'categories' => (array) ($o['categories'] ?? ['games']),
                'click_url_template' => (string) ($o['tracking_url'] ?? ''),
                'epc_score' => (float) ($o['epc'] ?? 0.0),
                'is_featured' => (bool) ($o['featured'] ?? false),
            ];
        }
        return $normalized;
    }
}
