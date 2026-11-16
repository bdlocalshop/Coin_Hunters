<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Payouts;

use App\Domain\Ledger\Exceptions\InsufficientBalanceException;
use App\Domain\Ledger\Services\LedgerService;
use App\Domain\Payouts\Models\CryptoPayoutMethod;
use App\Domain\Payouts\Models\WithdrawalRequest;
use App\Domain\Payouts\Services\PayoutService;
use App\Models\User;
use Database\Seeders\CryptoPayoutMethodSeeder;
use Database\Seeders\LedgerAccountSeeder;
use Database\Seeders\PlatformSettingSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

class PayoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayoutService $payoutService;
    private LedgerService $ledgerService;
    private User $user;
    private User $admin;
    private CryptoPayoutMethod $ltcMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LedgerAccountSeeder::class);
        $this->seed(PlatformSettingSeeder::class);
        $this->seed(CryptoPayoutMethodSeeder::class);

        $this->ledgerService = new LedgerService();
        $this->payoutService = new PayoutService($this->ledgerService);

        $this->user = User::factory()->create([
            'available_coins' => 10000,
            'pending_coins' => 0,
            'locked_withdrawal_coins' => 0,
            'lifetime_earned_coins' => 10000,
        ]);

        $this->admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->ltcMethod = CryptoPayoutMethod::where('symbol', 'LTC')->first();
    }

    public function test_user_can_request_withdrawal_and_locks_coins_in_escrow(): void
    {
        $validLtcAddress = 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc';

        $withdrawal = $this->payoutService->requestWithdrawal(
            user: $this->user,
            methodId: $this->ltcMethod->id,
            coins: 5000, // $5.00 USD
            walletAddress: $validLtcAddress
        );

        $this->assertNotNull($withdrawal->id);
        $this->assertNotNull($withdrawal->uuid);
        $this->assertEquals('pending', $withdrawal->status);
        $this->assertEquals(5000, $withdrawal->coins_amount);
        $this->assertEquals(5.00, $withdrawal->usd_amount);
        $this->assertEquals($validLtcAddress, $withdrawal->wallet_address);

        $this->user->refresh();
        $this->assertEquals(5000, $this->user->available_coins);
        $this->assertEquals(5000, $this->user->locked_withdrawal_coins);
        $this->assertEquals(10000, $this->user->lifetime_earned_coins); // Not affected
    }

    public function test_withdrawal_below_minimum_threshold_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // LTC min_coins is 1,000, requesting only 500
        $this->payoutService->requestWithdrawal(
            user: $this->user,
            methodId: $this->ltcMethod->id,
            coins: 500,
            walletAddress: 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc'
        );
    }

    public function test_withdrawal_with_invalid_wallet_address_regex_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // Invalid LTC address (starts with 0x)
        $this->payoutService->requestWithdrawal(
            user: $this->user,
            methodId: $this->ltcMethod->id,
            coins: 2000,
            walletAddress: '0x1234567890abcdef1234567890abcdef12345678'
        );
    }

    public function test_withdrawal_exceeding_available_balance_throws_exception(): void
    {
        $this->expectException(InsufficientBalanceException::class);

        // User has 10,000 coins, requesting 15,000
        $this->payoutService->requestWithdrawal(
            user: $this->user,
            methodId: $this->ltcMethod->id,
            coins: 15000,
            walletAddress: 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc'
        );
    }

    public function test_admin_approving_withdrawal_requires_tx_hash_and_finalizes_ledger(): void
    {
        $withdrawal = $this->payoutService->requestWithdrawal(
            user: $this->user,
            methodId: $this->ltcMethod->id,
            coins: 4000,
            walletAddress: 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc'
        );

        $txHash = '3a9f0e84b2c1d9f8e7a6b5c4d3e2f1a0b9c8d7e6f5a4b3c2d1e0f9a8b7c6d5e4';

        $approved = $this->payoutService->approveWithdrawal(
            withdrawal: $withdrawal,
            admin: $this->admin,
            txHash: $txHash
        );

        $this->assertEquals('approved', $approved->status);
        $this->assertEquals($txHash, $approved->tx_hash);
        $this->assertEquals($this->admin->id, $approved->reviewed_by_admin_id);
        $this->assertNotNull($approved->reviewed_at);

        $this->user->refresh();
        $this->assertEquals(6000, $this->user->available_coins);
        $this->assertEquals(0, $this->user->locked_withdrawal_coins);

        $this->assertDatabaseHas('ledger_transactions', [
            'type' => 'withdrawal_approved',
            'reference_id' => $withdrawal->uuid,
        ]);
    }

    public function test_admin_rejecting_withdrawal_refunds_locked_coins_to_user(): void
    {
        $withdrawal = $this->payoutService->requestWithdrawal(
            user: $this->user,
            methodId: $this->ltcMethod->id,
            coins: 3000,
            walletAddress: 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc'
        );

        $this->user->refresh();
        $this->assertEquals(7000, $this->user->available_coins);
        $this->assertEquals(3000, $this->user->locked_withdrawal_coins);

        $rejected = $this->payoutService->rejectWithdrawal(
            withdrawal: $withdrawal,
            admin: $this->admin,
            reason: 'Suspicious proxy activity detected'
        );

        $this->assertEquals('rejected', $rejected->status);
        $this->assertEquals('Suspicious proxy activity detected', $rejected->rejection_reason);

        // Fully refunded
        $this->user->refresh();
        $this->assertEquals(10000, $this->user->available_coins);
        $this->assertEquals(0, $this->user->locked_withdrawal_coins);

        $this->assertDatabaseHas('ledger_transactions', [
            'type' => 'withdrawal_rejected',
            'reference_id' => $withdrawal->uuid,
        ]);
    }

    public function test_already_processed_withdrawal_cannot_be_approved_or_rejected(): void
    {
        $withdrawal = $this->payoutService->requestWithdrawal(
            user: $this->user,
            methodId: $this->ltcMethod->id,
            coins: 2000,
            walletAddress: 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc'
        );

        $this->payoutService->approveWithdrawal($withdrawal, $this->admin, 'tx_hash_1');

        $this->expectException(DomainException::class);
        $this->payoutService->rejectWithdrawal($withdrawal, $this->admin, 'cannot reject approved');
    }

    public function test_hourly_rate_limit_blocks_excessive_withdrawal_requests(): void
    {
        $addr = 'LQTpS3VaYTuo8Nx8FkK9H8y2bTqE4z1aBc';

        // 3 valid requests
        $this->payoutService->requestWithdrawal($this->user, $this->ltcMethod->id, 1000, $addr);
        $this->payoutService->requestWithdrawal($this->user, $this->ltcMethod->id, 1000, $addr);
        $this->payoutService->requestWithdrawal($this->user, $this->ltcMethod->id, 1000, $addr);

        // 4th request within hour should be rate-limited
        $this->expectException(TooManyRequestsHttpException::class);
        $this->payoutService->requestWithdrawal($this->user, $this->ltcMethod->id, 1000, $addr);
    }
}
