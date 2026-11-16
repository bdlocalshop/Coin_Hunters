<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Referrals;

use App\Domain\Ledger\Services\LedgerService;
use App\Domain\Referrals\Services\ReferralService;
use App\Models\User;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\PlatformSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $referralService;
    private LedgerService $ledgerService;
    private User $referrer;
    private User $referredUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LedgerAccountSeeder::class);
        $this->seed(PlatformSettingSeeder::class);

        $this->ledgerService = new LedgerService();
        $this->referralService = new ReferralService($this->ledgerService);

        $this->referrer = User::factory()->create([
            'available_coins' => 0,
            'lifetime_earned_coins' => 0,
            'status' => 'active',
        ]);

        $this->referredUser = User::factory()->create([
            'referred_by_id' => $this->referrer->id,
            'referral_bonus_paid' => false,
            'available_coins' => 0,
            'lifetime_earned_coins' => 0,
        ]);
    }

    public function test_referred_user_below_threshold_does_not_qualify(): void
    {
        // Earn only 500 coins (threshold is 1,000 coins)
        $this->ledgerService->creditOfferEarning(
            user: $this->referredUser,
            coins: 500,
            offerId: 'offer_below_thresh',
            idempotencyKey: 'idem_below_thresh'
        );

        $this->referredUser->refresh();
        $this->assertEquals(500, $this->referredUser->lifetime_earned_coins);

        $tx = $this->referralService->checkAndRewardQualification($this->referredUser);

        $this->assertNull($tx);
        $this->assertFalse($this->referredUser->referral_bonus_paid);

        $this->referrer->refresh();
        $this->assertEquals(0, $this->referrer->available_coins);
    }

    public function test_referred_user_reaching_threshold_triggers_flat_referrer_bonus(): void
    {
        // Earn 1,000 coins meeting the qualification threshold
        $this->ledgerService->creditOfferEarning(
            user: $this->referredUser,
            coins: 1000,
            offerId: 'offer_qualifying',
            idempotencyKey: 'idem_qualifying_offer'
        );

        $this->referredUser->refresh();
        $this->assertEquals(1000, $this->referredUser->lifetime_earned_coins);

        $tx = $this->referralService->checkAndRewardQualification($this->referredUser);

        $this->assertNotNull($tx);
        $this->assertEquals('referral_bonus', $tx->type);

        $this->referredUser->refresh();
        $this->assertTrue($this->referredUser->referral_bonus_paid);

        // Referrer receives 500 flat bonus coins
        $this->referrer->refresh();
        $this->assertEquals(500, $this->referrer->available_coins);
        $this->assertEquals(500, $this->referrer->lifetime_earned_coins);
    }

    public function test_referral_qualification_bonus_cannot_be_paid_twice(): void
    {
        // Reach 1,500 coins
        $this->ledgerService->creditOfferEarning(
            user: $this->referredUser,
            coins: 1500,
            offerId: 'offer_big',
            idempotencyKey: 'idem_big_offer'
        );

        $this->referredUser->refresh();

        // First qualification reward
        $tx1 = $this->referralService->checkAndRewardQualification($this->referredUser);
        $this->assertNotNull($tx1);

        $this->referrer->refresh();
        $this->assertEquals(500, $this->referrer->available_coins);

        // Second check should be a no-op
        $tx2 = $this->referralService->checkAndRewardQualification($this->referredUser);
        $this->assertNull($tx2);

        $this->referrer->refresh();
        $this->assertEquals(500, $this->referrer->available_coins); // Still 500, not 1000
    }

    public function test_user_without_referrer_does_not_trigger_bonus(): void
    {
        $independentUser = User::factory()->create([
            'referred_by_id' => null,
            'lifetime_earned_coins' => 2000,
        ]);

        $tx = $this->referralService->checkAndRewardQualification($independentUser);
        $this->assertNull($tx);
    }
}
