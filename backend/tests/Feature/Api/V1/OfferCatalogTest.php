<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Offerwalls\Models\Offer;
use App\Domain\Offerwalls\Models\OfferClick;
use App\Models\User;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\OfferwallProviderSeeder;
use Database\Seeders\PlatformSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OfferCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LedgerAccountSeeder::class);
        $this->seed(PlatformSettingSeeder::class);
        $this->seed(OfferwallProviderSeeder::class);

        $this->user = User::factory()->create();

        // Sync campaigns
        Artisan::call('offers:sync');
    }

    public function test_authenticated_user_can_fetch_offer_catalog(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/offers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'current_page',
                'data' => [
                    '*' => [
                        'id',
                        'provider_slug',
                        'external_offer_id',
                        'title',
                        'user_payout_coins',
                        'device_targets',
                        'categories',
                    ],
                ],
                'total',
            ]);

        $this->assertGreaterThan(0, $response->json('total'));
    }

    public function test_can_filter_offers_by_device_and_category(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/offers?device=android&category=games');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $item) {
            $this->assertContains('games', $item['categories']);
            $this->assertContains('android', $item['device_targets']);
        }
    }

    public function test_authenticated_user_can_fetch_live_surveys(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/surveys');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'count',
                'surveys' => [
                    '*' => [
                        'id',
                        'provider',
                        'title',
                        'payout_coins',
                        'duration_minutes',
                        'click_url',
                    ],
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('count'));
    }

    public function test_authenticated_user_can_fetch_offerwall_cards(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/offer-walls');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'walls' => [
                    '*' => [
                        'id',
                        'slug',
                        'name',
                        'bonus_multiplier',
                        'url',
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('walls'));
    }

    public function test_user_can_generate_tracked_offer_click(): void
    {
        $offer = Offer::first();
        $this->assertNotNull($offer);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/offers/{$offer->id}/click", [
                'device_hash' => 'test_click_hash_1',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'click_uuid',
                'redirect_url',
                'offer',
            ]);

        $clickUuid = $response->json('click_uuid');

        $this->assertDatabaseHas('offer_clicks', [
            'click_uuid' => $clickUuid,
            'user_id' => $this->user->id,
            'offer_id' => $offer->id,
            'status' => 'clicked',
        ]);
    }

    public function test_offer_click_is_blocked_if_fraud_detected(): void
    {
        $offer = Offer::first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/v1/offers/{$offer->id}/click", [
                'device_hash' => 'bot_hash',
                'is_webdriver' => true,
            ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_view_my_offers_tracking(): void
    {
        $offer = Offer::first();

        OfferClick::create([
            'click_uuid' => 'test_uuid_my_offers',
            'user_id' => $this->user->id,
            'offer_id' => $offer->id,
            'provider_slug' => $offer->provider_slug,
            'ip_address' => '127.0.0.1',
            'status' => 'clicked',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/my-offers?tab=all');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.click_uuid', 'test_uuid_my_offers');
    }
}
