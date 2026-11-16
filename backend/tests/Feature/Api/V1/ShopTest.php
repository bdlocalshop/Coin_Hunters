<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Payouts\Models\CryptoPayoutMethod;
use App\Models\User;
use Database\Seeders\CryptoPayoutMethodSeeder;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\PlatformSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LedgerAccountSeeder::class);
        $this->seed(PlatformSettingSeeder::class);
        $this->seed(CryptoPayoutMethodSeeder::class);

        $this->user = User::factory()->create([
            'available_coins' => 20000,
            'lifetime_earned_coins' => 25000,
        ]);
    }

    public function test_can_fetch_active_crypto_payout_methods(): void
    {
        $response = $this->getJson('/api/v1/shop/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'methods' => [
                    '*' => [
                        'id',
                        'symbol',
                        'name',
                        'network',
                        'icon_url',
                        'min_coins',
                        'min_usd',
                    ],
                ],
            ]);

        $this->assertCount(4, $response->json('methods'));
    }

    public function test_authenticated_user_can_submit_withdrawal_request(): void
    {
        $method = CryptoPayoutMethod::where('symbol', 'LTC')->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/shop/withdraw', [
                'crypto_payout_method_id' => $method->id,
                'coins' => 5000,
                'wallet_address' => 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('withdrawal.status', 'pending')
            ->assertJsonPath('withdrawal.coins_amount', 5000)
            ->assertJsonPath('withdrawal.symbol', 'LTC');

        $this->user->refresh();
        $this->assertEquals(15000, $this->user->available_coins);
        $this->assertEquals(5000, $this->user->locked_withdrawal_coins);
    }

    public function test_user_cannot_withdraw_below_minimum(): void
    {
        $method = CryptoPayoutMethod::where('symbol', 'BTC')->first(); // min 15,000

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/shop/withdraw', [
                'crypto_payout_method_id' => $method->id,
                'coins' => 2000, // below 15,000
                'wallet_address' => '1BvBMSEYstWetqTFn5Au4m4GFg7xJaNVN2',
            ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_view_withdrawal_history(): void
    {
        $method = CryptoPayoutMethod::where('symbol', 'LTC')->first();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/shop/withdraw', [
                'crypto_payout_method_id' => $method->id,
                'coins' => 3000,
                'wallet_address' => 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/shop/withdrawals');

        $response->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.coins_amount', 3000);
    }

    public function test_public_platform_stats_and_ticker(): void
    {
        $response = $this->getJson('/api/v1/public/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'metrics' => [
                    'avg_time_to_cashout',
                    'avg_daily_earnings',
                    'total_usd_paid_out',
                    'total_active_users',
                ],
                'ticker',
            ]);
    }

    public function test_public_leaderboard(): void
    {
        $response = $this->getJson('/api/v1/leaderboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'leaderboard' => [
                    '*' => [
                        'rank',
                        'username',
                        'lifetime_earned_coins',
                        'lifetime_usd',
                    ],
                ],
            ]);

        $this->assertGreaterThan(0, count($response->json('leaderboard')));
    }
}
