<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Fraud\DTOs\DeviceInspectionDTO;
use App\Domain\Fraud\Services\FraudGuardService;
use App\Domain\Offerwalls\Models\Offer;
use App\Domain\Offerwalls\Models\OfferClick;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Domain\Offerwalls\Services\OfferwallRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OfferController extends Controller
{
    public function __construct(
        private readonly OfferwallRegistry $registry,
        private readonly FraudGuardService $fraudGuard
    ) {}

    /**
     * Get paginated offer catalog with device, category, and provider filters.
     */
    public function index(Request $request): JsonResponse
    {
        $device = $request->query('device');
        $category = $request->query('category');
        $provider = $request->query('provider');
        $sort = $request->query('sort', 'featured');
        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 24), 50);

        $cacheKey = sprintf('offers_feed_%s_%s_%s_%s_p%d_pp%d', $device ?? 'all', $category ?? 'all', $provider ?? 'all', $sort, $page, $perPage);

        $fetchQuery = function () use ($device, $category, $provider, $sort, $perPage) {
            $query = Offer::where('is_active', true);

            if ($device && $device !== 'all') {
                $query->whereJsonContains('device_targets', strtolower($device));
            }

            if ($category && $category !== 'all') {
                $query->whereJsonContains('categories', strtolower($category));
            }

            if ($provider && $provider !== 'all') {
                $query->where('provider_slug', $provider);
            }

            switch ($sort) {
                case 'payout_desc':
                    $query->orderByDesc('user_payout_coins');
                    break;
                case 'payout_asc':
                    $query->orderBy('user_payout_coins');
                    break;
                case 'popular':
                    $query->orderByDesc('epc_score');
                    break;
                case 'featured':
                default:
                    $query->orderByDesc('is_featured')
                        ->orderByDesc('epc_score')
                        ->orderByDesc('user_payout_coins');
                    break;
            }

            return $query->paginate($perPage);
        };

        try {
            $results = Cache::remember($cacheKey, 60, $fetchQuery);
        } catch (\Throwable) {
            $results = $fetchQuery();
        }

        return response()->json($results);
    }

    /**
     * Get live matched surveys from integrated survey providers (BitLabs, CPX Research).
     */
    public function surveys(Request $request): JsonResponse
    {
        $user = $request->user();
        $surveyProviders = OfferwallProvider::whereIn('slug', ['bitlabs', 'cpx_research'])
            ->where('is_active', true)
            ->get();

        $allSurveys = [];

        foreach ($surveyProviders as $provider) {
            if ($this->registry->hasAdapter($provider->slug)) {
                $adapter = $this->registry->getAdapter($provider->slug);
                $surveys = $adapter->fetchSurveys($provider, $user);
                foreach ($surveys as $survey) {
                    $allSurveys[] = $survey;
                }
            }
        }

        // Sort surveys by reward desc
        usort($allSurveys, fn ($a, $b) => $b['payout_coins'] <=> $a['payout_coins']);

        return response()->json([
            'count' => count($allSurveys),
            'surveys' => $allSurveys,
        ]);
    }

    /**
     * Get active partner wall cards with promotional multiplier badges.
     */
    public function offerwalls(Request $request): JsonResponse
    {
        $user = $request->user();
        $providers = OfferwallProvider::where('is_active', true)->get();

        $walls = $providers->map(function (OfferwallProvider $p) use ($user) {
            $bonusPercent = (int) round(($p->bonus_multiplier - 1.0) * 100);
            $bonusBadge = $bonusPercent > 0 ? "+{$bonusPercent}% Promo Boost" : null;

            // Generate wall launch URL
            $url = match ($p->slug) {
                'offerwall_gg' => "https://offerwall.gg/wall?app_id={$p->app_id}&user_id={$user->id}",
                'offerwall_com' => "https://offerwall.com/wall?api_key={$p->api_key}&user_id={$user->id}",
                'lootably' => "https://lootably.com/wall?placement_id={$p->app_id}&user_id={$user->id}",
                'bitlabs' => "https://web.bitlabs.ai/?token={$p->app_id}&uid={$user->id}",
                'cpx_research' => "https://offers.cpx-research.com/index.php?app_id={$p->app_id}&ext_user_id={$user->id}",
                default => '#',
            };

            return [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'logo_url' => $p->logo_url,
                'bonus_multiplier' => $p->bonus_multiplier,
                'bonus_badge' => $bonusBadge,
                'url' => $url,
            ];
        });

        return response()->json(['walls' => $walls]);
    }

    /**
     * Get authenticated user's offer click and conversion tracking history.
     */
    public function myOffers(Request $request): JsonResponse
    {
        $user = $request->user();
        $tab = $request->query('tab', 'all');

        $query = OfferClick::where('user_id', $user->id)
            ->with(['offer' => fn ($q) => $q->select('id', 'title', 'icon_url', 'user_payout_coins', 'provider_slug')])
            ->orderByDesc('created_at');

        if ($tab !== 'all' && in_array($tab, ['clicked', 'pending', 'completed', 'reversed'], true)) {
            $query->where('status', $tab);
        }

        $clicks = $query->paginate(20);

        return response()->json($clicks);
    }

    /**
     * Generate tracked click UUID and signed tracking URL for outbound offer.
     */
    public function click(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        $offer = Offer::with('provider')->findOrFail($id);

        if (!$offer->is_active) {
            return response()->json(['message' => 'This offer is currently inactive.'], 404);
        }

        $deviceHash = (string) $request->input('device_hash', 'web_click_' . $user->id);

        // Run anti-fraud screening before generating click
        $deviceDto = new DeviceInspectionDTO(
            ipAddress: $request->ip() ?: '127.0.0.1',
            deviceHash: $deviceHash,
            userAgent: $request->userAgent(),
            isWebdriver: (bool) $request->input('is_webdriver', false),
            isEmulator: (bool) $request->input('is_emulator', false),
        );

        $this->fraudGuard->inspectAndEnforce($deviceDto, currentUserId: $user->id);

        $clickUuid = (string) Str::uuid();

        // Create click log
        OfferClick::create([
            'click_uuid' => $clickUuid,
            'user_id' => $user->id,
            'offer_id' => $offer->id,
            'provider_slug' => $offer->provider_slug,
            'ip_address' => $deviceDto->ipAddress,
            'device_hash' => $deviceHash,
            'status' => 'clicked',
        ]);

        // Inject tracking parameters
        $provider = $offer->provider;
        $trackingUrl = str_replace(
            ['{click_uuid}', '{user_id}', '{app_id}', '{api_key}'],
            [
                urlencode($clickUuid),
                urlencode((string) $user->id),
                urlencode((string) ($provider?->app_id ?? '')),
                urlencode((string) ($provider?->api_key ?? '')),
            ],
            $offer->click_url_template
        );

        return response()->json([
            'click_uuid' => $clickUuid,
            'redirect_url' => $trackingUrl,
            'offer' => [
                'id' => $offer->id,
                'title' => $offer->title,
                'user_payout_coins' => $offer->user_payout_coins,
            ],
        ]);
    }
}
