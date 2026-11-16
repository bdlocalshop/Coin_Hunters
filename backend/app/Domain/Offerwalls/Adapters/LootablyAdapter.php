<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Adapters;

use App\Domain\Offerwalls\DTOs\PostbackPayloadDTO;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Models\User;
use Illuminate\Http\Request;

class LootablyAdapter implements OfferwallAdapterInterface
{
    public function getProviderSlug(): string
    {
        return 'lootably';
    }

    public function fetchCampaigns(OfferwallProvider $provider, array $filters = []): array
    {
        return [
            [
                'external_offer_id' => 'loot_genshin_impact',
                'title' => 'Genshin Impact — Adventure Rank 20',
                'description' => 'Embark on an open-world anime RPG adventure. Download and achieve Adventure Rank 20.',
                'requirements' => ['Install desktop/mobile client', 'Reach AR 20 within 14 days'],
                'icon_url' => 'https://images.unsplash.com/photo-1579373903781-fd5c0c30c4cd?w=200&auto=format&fit=crop&q=80',
                'network_payout_usd' => 18.00,
                'user_payout_coins' => (int) round(18.00 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'device_targets' => ['desktop', 'android', 'ios'],
                'countries' => ['ALL'],
                'categories' => ['games', 'rpg'],
                'click_url_template' => 'https://lootably.com/offer?placement_id={app_id}&user_id={user_id}&sub_id={click_uuid}',
                'epc_score' => 1.8500,
                'is_featured' => true,
            ],
            [
                'external_offer_id' => 'loot_nord_vpn',
                'title' => 'NordVPN — 2-Year Plan Subscription',
                'description' => 'Subscribe to any 2-year NordVPN plan and maintain active account for 30+ days.',
                'requirements' => ['Purchase 2-year subscription', 'Valid payment method'],
                'icon_url' => 'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=200&auto=format&fit=crop&q=80',
                'network_payout_usd' => 50.00,
                'user_payout_coins' => (int) round(50.00 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'device_targets' => ['desktop', 'android', 'ios'],
                'countries' => ['US', 'CA', 'GB', 'DE', 'AU', 'NL', 'SE'],
                'categories' => ['security', 'apps'],
                'click_url_template' => 'https://lootably.com/offer?placement_id={app_id}&user_id={user_id}&sub_id={click_uuid}',
                'epc_score' => 2.1000,
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
        $providedHash = (string) ($request->input('hash') ?? $request->header('X-Lootably-Hash') ?? '');
        $providedSecret = (string) $request->input('secret');

        if (!empty($providedSecret) && hash_equals($secret, $providedSecret)) {
            return true;
        }

        $userId = (string) $request->input('userID', $request->input('user_id'));
        $ip = (string) $request->input('ip', $request->ip());
        $revenue = (string) $request->input('revenue', $request->input('amount'));

        $expectedHash = hash('sha256', $userId . ':' . $ip . ':' . $revenue . ':' . $secret);
        return hash_equals($expectedHash, $providedHash);
    }

    public function parsePostback(Request $request, OfferwallProvider $provider): PostbackPayloadDTO
    {
        $txId = (string) ($request->input('transactionID') ?? $request->input('transaction_id') ?? uniqid('tx_loot_'));
        $userId = (int) $request->input('userID', $request->input('user_id'));
        $clickUuid = $request->input('subID') ?? $request->input('sub_id') ?? $request->input('click_uuid');
        $offerId = (string) $request->input('offerID', 'unknown');

        $revenue = (float) $request->input('revenue', $request->input('payout', 0.0));
        $status = (int) $request->input('status', 1);
        $isReversal = $status === 0 || $revenue < 0;

        $coinsInput = $request->input('reward');
        if ($coinsInput !== null) {
            $payoutCoins = abs((int) $coinsInput);
        } else {
            $payoutCoins = (int) round(abs($revenue) * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier);
        }

        return new PostbackPayloadDTO(
            providerSlug: $this->getProviderSlug(),
            externalTransactionId: $txId,
            userId: $userId,
            payoutCoins: $payoutCoins,
            payoutUsd: abs($revenue),
            isReversal: $isReversal,
            clickUuid: $clickUuid,
            offerId: $offerId,
            reversalReason: $isReversal ? 'Lootably chargeback reversal' : null,
            rawPayload: $request->all(),
        );
    }
}
