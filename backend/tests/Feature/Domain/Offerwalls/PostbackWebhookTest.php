<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Offerwalls;

use App\Domain\Offerwalls\Models\Offer;
use App\Domain\Offerwalls\Models\OfferClick;
use App\Domain\Offerwalls\Models\OfferHold;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Models\User;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\OfferwallProviderSeeder;
use Database\Seeders\PlatformSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PostbackWebhookTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private OfferwallProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LedgerAccountSeeder::class);
        $this->seed(PlatformSettingSeeder::class);
        $this->seed(OfferwallProviderSeeder::class);

        $this->user = User::factory()->create([
            'available_coins' => 0,
            'pending_coins' => 0,
            'lifetime_earned_coins' => 0,
        ]);

        $this->provider = OfferwallProvider::where('slug', 'offerwall_gg')->first();
    }

    public function test_valid_postback_credits_user_available_coins(): void
    {
        $txId = 'tx_valid_001';
        $payoutUsd = '2.50';
        $secret = $this->provider->secret_key;
        $sig = md5($secret . $this->user->id . $payoutUsd . $txId);

        $response = $this->postJson('/api/v1/postbacks/offerwall_gg', [
            'transaction_id' => $txId,
            'user_id' => $this->user->id,
            'payout' => $payoutUsd,
            'signature' => $sig,
            'offer_id' => 'offer_test_1',
            'status' => 1,
        ], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('result', 1);

        $this->user->refresh();
        // $2.50 * 1000 * 70% * 1.15 multiplier = 2012 coins or similar
        $this->assertGreaterThan(0, $this->user->available_coins);
        $this->assertEquals($this->user->available_coins, $this->user->lifetime_earned_coins);
        $this->assertEquals(0, $this->user->pending_coins);

        $this->assertDatabaseHas('postback_logs', [
            'provider_slug' => 'offerwall_gg',
            'external_transaction_id' => $txId,
            'status' => 'credited',
        ]);
    }

    public function test_postback_from_unauthorized_ip_returns_403_forbidden(): void
    {
        $response = $this->postJson('/api/v1/postbacks/offerwall_gg', [
            'transaction_id' => 'tx_rogue_ip',
            'user_id' => $this->user->id,
            'payout' => '10.00',
        ], [
            'REMOTE_ADDR' => '198.51.100.25', // Not in allowed_ips
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('postback_logs', [
            'provider_slug' => 'offerwall_gg',
            'status' => 'rejected_ip',
        ]);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->available_coins);
    }

    public function test_postback_with_invalid_signature_returns_401_unauthorized(): void
    {
        $response = $this->postJson('/api/v1/postbacks/offerwall_gg', [
            'transaction_id' => 'tx_bad_sig',
            'user_id' => $this->user->id,
            'payout' => '5.00',
            'signature' => 'invalid_tampered_signature_hex',
        ], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $response->assertStatus(401)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Invalid signature.');

        $this->assertDatabaseHas('postback_logs', [
            'provider_slug' => 'offerwall_gg',
            'status' => 'rejected_sig',
        ]);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->available_coins);
    }

    public function test_duplicate_postback_is_handled_idempotently_without_double_crediting(): void
    {
        $txId = 'tx_duplicate_test';
        $payoutUsd = '1.00';
        $secret = $this->provider->secret_key;
        $sig = md5($secret . $this->user->id . $payoutUsd . $txId);

        $payload = [
            'transaction_id' => $txId,
            'user_id' => $this->user->id,
            'payout' => $payoutUsd,
            'signature' => $sig,
            'offer_id' => 'offer_idem',
        ];

        // First delivery
        $res1 = $this->postJson('/api/v1/postbacks/offerwall_gg', $payload, ['REMOTE_ADDR' => '127.0.0.1']);
        $res1->assertStatus(200);

        $this->user->refresh();
        $creditedBalance = $this->user->available_coins;
        $this->assertGreaterThan(0, $creditedBalance);

        // Second duplicate delivery
        $res2 = $this->postJson('/api/v1/postbacks/offerwall_gg', $payload, ['REMOTE_ADDR' => '127.0.0.1']);
        $res2->assertStatus(200)
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('message', 'Duplicate postback received. Handled idempotently.');

        // Verify balance was NOT double credited
        $this->user->refresh();
        $this->assertEquals($creditedBalance, $this->user->available_coins);

        $this->assertDatabaseHas('postback_logs', [
            'provider_slug' => 'offerwall_gg',
            'external_transaction_id' => $txId,
            'status' => 'duplicate',
        ]);
    }

    public function test_high_value_postback_routes_to_pending_hold_and_auto_releases(): void
    {
        // Payout of $10.00 USD produces ~8,050 Coins (>= 5,000 Coins hold threshold)
        $txId = 'tx_high_value_hold';
        $payoutUsd = '10.00';
        $secret = $this->provider->secret_key;
        $sig = md5($secret . $this->user->id . $payoutUsd . $txId);

        $response = $this->postJson('/api/v1/postbacks/offerwall_gg', [
            'transaction_id' => $txId,
            'user_id' => $this->user->id,
            'payout' => $payoutUsd,
            'signature' => $sig,
            'offer_id' => 'high_payout_game',
        ], ['REMOTE_ADDR' => '127.0.0.1']);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->available_coins);
        $this->assertGreaterThanOrEqual(5000, $this->user->pending_coins);
        $this->assertGreaterThanOrEqual(5000, $this->user->lifetime_earned_coins);

        $hold = OfferHold::where('external_transaction_id', $txId)->first();
        $this->assertNotNull($hold);
        $this->assertEquals('pending', $hold->status);

        // Simulate expiry of hold (move release_at into past)
        $hold->release_at = now()->subHour();
        $hold->save();

        // Run scheduled release command
        Artisan::call('ledger:release-holds');

        $this->user->refresh();
        $this->assertGreaterThanOrEqual(5000, $this->user->available_coins);
        $this->assertEquals(0, $this->user->pending_coins);

        $hold->refresh();
        $this->assertEquals('released', $hold->status);
    }

    public function test_chargeback_reversal_posts_compensating_ledger_transaction(): void
    {
        // 1. Initial conversion
        $txId = 'tx_to_be_reversed';
        $payoutUsd = '3.00';
        $secret = $this->provider->secret_key;
        $sig1 = md5($secret . $this->user->id . $payoutUsd . $txId);

        $this->postJson('/api/v1/postbacks/offerwall_gg', [
            'transaction_id' => $txId,
            'user_id' => $this->user->id,
            'payout' => $payoutUsd,
            'signature' => $sig1,
            'offer_id' => 'offer_rev',
        ], ['REMOTE_ADDR' => '127.0.0.1']);

        $this->user->refresh();
        $initialCoins = $this->user->available_coins;
        $this->assertGreaterThan(0, $initialCoins);

        // 2. Reversal postback arrives (status = 2)
        $sig2 = md5($secret . $this->user->id . $payoutUsd . $txId);

        $response = $this->postJson('/api/v1/postbacks/offerwall_gg', [
            'transaction_id' => $txId . '_rev',
            'user_id' => $this->user->id,
            'payout' => $payoutUsd,
            'status' => 2, // Reversal
            'signature' => $this->provider->secret_key, // Secret token match
            'secret' => $this->provider->secret_key,
            'reason' => 'Proxy fraud detected by advertiser',
        ], ['REMOTE_ADDR' => '127.0.0.1']);

        $response->assertStatus(200)
            ->assertJsonPath('result', 'reversed');

        $this->user->refresh();
        $this->assertEquals(0, $this->user->available_coins);

        $this->assertDatabaseHas('ledger_transactions', [
            'type' => 'chargeback',
        ]);
    }

    public function test_postback_triggers_flat_referral_qualification(): void
    {
        $referrer = User::factory()->create([
            'available_coins' => 0,
            'lifetime_earned_coins' => 0,
        ]);

        $this->user->referred_by_id = $referrer->id;
        $this->user->save();

        // Send a postback earning $2.00 USD (qualifies for the 1,000 coin threshold)
        $txId = 'tx_referral_trigger_1';
        $payoutUsd = '2.00';
        $secret = $this->provider->secret_key;
        $sig = md5($secret . $this->user->id . $payoutUsd . $txId);

        $this->postJson('/api/v1/postbacks/offerwall_gg', [
            'transaction_id' => $txId,
            'user_id' => $this->user->id,
            'payout' => $payoutUsd,
            'signature' => $sig,
        ], ['REMOTE_ADDR' => '127.0.0.1']);

        $this->user->refresh();
        $this->assertTrue($this->user->referral_bonus_paid);

        // Referrer receives 500 flat bonus coins
        $referrer->refresh();
        $this->assertEquals(500, $referrer->available_coins);
    }
}
