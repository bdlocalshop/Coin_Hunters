<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Adapters;

use App\Domain\Offerwalls\DTOs\PostbackPayloadDTO;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CpxResearchAdapter implements OfferwallAdapterInterface
{
    public function getProviderSlug(): string
    {
        return 'cpx_research';
    }

    public function fetchCampaigns(OfferwallProvider $provider, array $filters = []): array
    {
        return [
            [
                'external_offer_id' => 'cpx_daily_survey_router',
                'title' => 'CPX Research Daily Surveys',
                'description' => 'Unlimited daily targeted surveys from global Fortune 500 academic and marketing researchers.',
                'requirements' => ['Complete surveys attentively', 'Consistent responses'],
                'icon_url' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=200&auto=format&fit=crop&q=80',
                'network_payout_usd' => 3.00,
                'user_payout_coins' => (int) round(3.00 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'device_targets' => ['desktop', 'android', 'ios'],
                'countries' => ['ALL'],
                'categories' => ['surveys'],
                'click_url_template' => 'https://offers.cpx-research.com/index.php?app_id={app_id}&ext_user_id={user_id}&subid_1={click_uuid}',
                'epc_score' => 2.8000,
                'is_featured' => true,
            ],
        ];
    }

    public function fetchSurveys(OfferwallProvider $provider, User $user, array $params = []): array
    {
        if (!empty($provider->api_key) && !str_starts_with($provider->api_key, 'test_')) {
            try {
                $response = Http::timeout(3)->get('https://api.cpx-research.com/api/get-surveys.php', [
                    'app_id' => $provider->app_id,
                    'ext_user_id' => $user->id,
                    'output_method' => 'api',
                ]);

                if ($response->successful()) {
                    $raw = $response->json('surveys') ?? [];
                    return $this->normalizeSurveys($raw, $provider, $user);
                }
            } catch (\Throwable $e) {
                Log::warning('CPX Research survey fetch failed: ' . $e->getMessage());
            }
        }

        return [
            [
                'id' => 'cpx_srv_201',
                'provider' => 'CPX Research',
                'provider_slug' => 'cpx_research',
                'title' => 'E-Commerce & Online Grocery Shopping Study',
                'payout_coins' => (int) round(1.50 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'duration_minutes' => 9,
                'rating' => 4.7,
                'loi_score' => 92,
                'category' => 'Retail & E-commerce',
                'click_url' => sprintf('https://offers.cpx-research.com/index.php?app_id=%s&ext_user_id=%d', $provider->app_id ?? 'demo', $user->id),
            ],
            [
                'id' => 'cpx_srv_202',
                'provider' => 'CPX Research',
                'provider_slug' => 'cpx_research',
                'title' => 'Mobile Gaming Habits & In-App Purchases',
                'payout_coins' => (int) round(2.40 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'duration_minutes' => 14,
                'rating' => 4.9,
                'loi_score' => 96,
                'category' => 'Gaming & Apps',
                'click_url' => sprintf('https://offers.cpx-research.com/index.php?app_id=%s&ext_user_id=%d', $provider->app_id ?? 'demo', $user->id),
            ],
            [
                'id' => 'cpx_srv_203',
                'provider' => 'CPX Research',
                'provider_slug' => 'cpx_research',
                'title' => 'Global Travel, Airlines & Hotel Loyalty',
                'payout_coins' => (int) round(4.10 * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier),
                'duration_minutes' => 20,
                'rating' => 4.9,
                'loi_score' => 99,
                'category' => 'Travel & Tourism',
                'click_url' => sprintf('https://offers.cpx-research.com/index.php?app_id=%s&ext_user_id=%d', $provider->app_id ?? 'demo', $user->id),
            ],
        ];
    }

    public function verifySignature(Request $request, OfferwallProvider $provider): bool
    {
        $secret = $provider->secret_key ?? '';
        $providedHash = (string) ($request->input('secure_hash') ?? $request->header('X-CPX-Hash') ?? '');
        $providedSecret = (string) $request->input('secret');

        if (!empty($providedSecret) && hash_equals($secret, $providedSecret)) {
            return true;
        }

        $transId = (string) $request->input('trans_id', $request->input('transaction_id'));
        $expectedMd5 = md5($transId . '-' . $secret);

        return hash_equals($expectedMd5, $providedHash);
    }

    public function parsePostback(Request $request, OfferwallProvider $provider): PostbackPayloadDTO
    {
        $transId = (string) ($request->input('trans_id') ?? $request->input('transaction_id') ?? uniqid('tx_cpx_'));
        $userId = (int) ($request->input('user_id') ?? $request->input('ext_user_id'));
        $clickUuid = $request->input('subid_1') ?? $request->input('click_uuid');
        $offerId = (string) $request->input('survey_id', 'cpx_survey');

        $amountUsd = (float) $request->input('amount_usd', $request->input('payout', 0.0));
        $status = (int) $request->input('status', 1);
        $isReversal = $status === 2 || $amountUsd < 0;

        $coins = (int) round(abs($amountUsd) * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier);

        return new PostbackPayloadDTO(
            providerSlug: $this->getProviderSlug(),
            externalTransactionId: $transId,
            userId: $userId,
            payoutCoins: $coins,
            payoutUsd: abs($amountUsd),
            isReversal: $isReversal,
            clickUuid: $clickUuid,
            offerId: $offerId,
            reversalReason: $isReversal ? 'CPX Research survey chargeback' : null,
            rawPayload: $request->all(),
        );
    }

    private function normalizeSurveys(array $rawSurveys, OfferwallProvider $provider, User $user): array
    {
        $normalized = [];
        foreach ($rawSurveys as $s) {
            $payout = (float) ($s['payout_publisher_usd'] ?? 0.0);
            $coins = (int) round($payout * 1000 * ($provider->payout_margin_percent / 100) * $provider->bonus_multiplier);

            $normalized[] = [
                'id' => (string) ($s['id'] ?? uniqid('cpx_')),
                'provider' => 'CPX Research',
                'provider_slug' => 'cpx_research',
                'title' => (string) ($s['title'] ?? 'Consumer Research Study'),
                'payout_coins' => $coins,
                'duration_minutes' => (int) ($s['loi'] ?? 10),
                'rating' => (float) ($s['score'] ?? 4.5),
                'loi_score' => 90,
                'category' => 'Survey',
                'click_url' => (string) ($s['link'] ?? sprintf('https://offers.cpx-research.com/index.php?app_id=%s&ext_user_id=%d', $provider->app_id, $user->id)),
            ];
        }
        return $normalized;
    }
}
