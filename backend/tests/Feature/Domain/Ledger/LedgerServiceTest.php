<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Ledger;

use App\Domain\Ledger\DTOs\LedgerEntryDTO;
use App\Domain\Ledger\DTOs\PostTransactionDTO;
use App\Domain\Ledger\Enums\AccountCode;
use App\Domain\Ledger\Enums\EntryDirection;
use App\Domain\Ledger\Enums\TransactionType;
use App\Domain\Ledger\Exceptions\DuplicateTransactionException;
use App\Domain\Ledger\Exceptions\ImmutableRecordException;
use App\Domain\Ledger\Exceptions\InsufficientBalanceException;
use App\Domain\Ledger\Exceptions\UnbalancedLedgerException;
use App\Domain\Ledger\Models\LedgerEntry;
use App\Domain\Ledger\Models\LedgerTransaction;
use App\Domain\Ledger\Services\LedgerService;
use App\Models\User;
use Database\Seeders\LedgerAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerService $ledgerService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LedgerAccountSeeder::class);
        $this->ledgerService = new LedgerService();

        $this->user = User::factory()->create([
            'available_coins' => 0,
            'pending_coins' => 0,
            'locked_withdrawal_coins' => 0,
            'lifetime_earned_coins' => 0,
        ]);
    }

    public function test_can_post_balanced_transaction_and_update_user_balances_atomically(): void
    {
        $dto = new PostTransactionDTO(
            type: TransactionType::OFFER_COMPLETION,
            description: 'Completed Survey: 1,500 Coins',
            idempotencyKey: 'test_tx_001',
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::ASSET_NETWORK_RECEIVABLES,
                    direction: EntryDirection::DEBIT,
                    amount: 1500,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_AVAILABLE_BALANCE,
                    direction: EntryDirection::CREDIT,
                    amount: 1500,
                    userId: $this->user->id,
                ),
            ],
            referenceType: 'offer',
            referenceId: 'survey_101',
        );

        $tx = $this->ledgerService->post($dto);

        $this->assertNotNull($tx->id);
        $this->assertNotNull($tx->uuid);
        $this->assertEquals('test_tx_001', $tx->idempotency_key);
        $this->assertCount(2, $tx->entries);

        $this->user->refresh();
        $this->assertEquals(1500, $this->user->available_coins);
        $this->assertEquals(1500, $this->user->lifetime_earned_coins);
        $this->assertEquals(0, $this->user->pending_coins);
        $this->assertEquals(0, $this->user->locked_withdrawal_coins);
    }

    public function test_unbalanced_transaction_throws_exception_and_rolls_back(): void
    {
        $this->expectException(UnbalancedLedgerException::class);

        $dto = new PostTransactionDTO(
            type: TransactionType::OFFER_COMPLETION,
            description: 'Unbalanced offer',
            idempotencyKey: 'test_unbalanced_001',
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::ASSET_NETWORK_RECEIVABLES,
                    direction: EntryDirection::DEBIT,
                    amount: 2000,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_AVAILABLE_BALANCE,
                    direction: EntryDirection::CREDIT,
                    amount: 1500, // Debits != Credits
                    userId: $this->user->id,
                ),
            ],
        );

        try {
            $this->ledgerService->post($dto);
        } finally {
            $this->user->refresh();
            $this->assertEquals(0, $this->user->available_coins);
            $this->assertDatabaseMissing('ledger_transactions', ['idempotency_key' => 'test_unbalanced_001']);
        }
    }

    public function test_zero_or_negative_amount_throws_exception(): void
    {
        $this->expectException(UnbalancedLedgerException::class);

        $dto = new PostTransactionDTO(
            type: TransactionType::MANUAL_ADJUSTMENT,
            description: 'Zero amount entry',
            idempotencyKey: 'test_zero_amount',
            entries: [
                new LedgerEntryDTO(
                    account: AccountCode::ASSET_NETWORK_RECEIVABLES,
                    direction: EntryDirection::DEBIT,
                    amount: 0,
                ),
                new LedgerEntryDTO(
                    account: AccountCode::LIABILITY_USER_AVAILABLE_BALANCE,
                    direction: EntryDirection::CREDIT,
                    amount: 0,
                    userId: $this->user->id,
                ),
            ],
        );

        $this->ledgerService->post($dto);
    }

    public function test_idempotency_prevents_duplicate_transactions(): void
    {
        $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 500,
            offerId: 'offer_abc',
            idempotencyKey: 'idem_key_unique_1',
            provider: 'bitlabs'
        );

        $this->user->refresh();
        $this->assertEquals(500, $this->user->available_coins);

        $this->expectException(DuplicateTransactionException::class);

        // Attempt duplicate posting with same idempotency key
        $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 500,
            offerId: 'offer_abc',
            idempotencyKey: 'idem_key_unique_1',
            provider: 'bitlabs'
        );
    }

    public function test_updating_ledger_transaction_throws_immutable_exception(): void
    {
        $tx = $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 1000,
            offerId: 'offer_immutable',
            idempotencyKey: 'idem_immutable_tx'
        );

        $this->expectException(ImmutableRecordException::class);

        $tx->description = 'Tampered description';
        $tx->save();
    }

    public function test_deleting_ledger_transaction_throws_immutable_exception(): void
    {
        $tx = $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 1000,
            offerId: 'offer_del_tx',
            idempotencyKey: 'idem_del_tx'
        );

        $this->expectException(ImmutableRecordException::class);

        $tx->delete();
    }

    public function test_updating_ledger_entry_throws_immutable_exception(): void
    {
        $tx = $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 1000,
            offerId: 'offer_immutable_entry',
            idempotencyKey: 'idem_immutable_entry'
        );

        $entry = $tx->entries->first();
        $this->assertNotNull($entry);

        $this->expectException(ImmutableRecordException::class);

        $entry->amount = 999999;
        $entry->save();
    }

    public function test_deleting_ledger_entry_throws_immutable_exception(): void
    {
        $tx = $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 1000,
            offerId: 'offer_del_entry',
            idempotencyKey: 'idem_del_entry'
        );

        $entry = $tx->entries->first();
        $this->assertNotNull($entry);

        $this->expectException(ImmutableRecordException::class);

        $entry->delete();
    }

    public function test_insufficient_available_balance_throws_exception(): void
    {
        $this->expectException(InsufficientBalanceException::class);

        // User starts with 0 available coins, attempting to lock 1,000 for withdrawal
        $this->ledgerService->lockWithdrawal(
            user: $this->user,
            coins: 1000,
            idempotencyKey: 'idem_withdrawal_fail',
            withdrawalId: 'wd_001',
            cryptoAsset: 'LTC'
        );
    }

    public function test_credit_offer_earning_on_hold_updates_pending_and_lifetime_earned(): void
    {
        $tx = $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 8000,
            offerId: 'high_value_offer_99',
            idempotencyKey: 'idem_high_value_1',
            provider: 'offerwall_gg',
            hold: true
        );

        $this->user->refresh();
        $this->assertEquals(0, $this->user->available_coins);
        $this->assertEquals(8000, $this->user->pending_coins);
        $this->assertEquals(8000, $this->user->lifetime_earned_coins);

        // Now release the pending coins
        $this->ledgerService->releasePendingCoins(
            user: $this->user,
            coins: 8000,
            idempotencyKey: 'idem_release_high_val',
            parentTransactionId: $tx->id
        );

        $this->user->refresh();
        $this->assertEquals(8000, $this->user->available_coins);
        $this->assertEquals(0, $this->user->pending_coins);
        // Lifetime earned remains 8,000 (not doubled to 16,000!)
        $this->assertEquals(8000, $this->user->lifetime_earned_coins);
    }

    public function test_lock_and_finalize_crypto_withdrawal_flow(): void
    {
        // 1. Initial credit
        $this->ledgerService->creditBonus(
            user: $this->user,
            coins: 5000,
            type: TransactionType::SIGNUP_BONUS,
            idempotencyKey: 'idem_bonus_wd',
            description: 'Signup Bonus'
        );

        $this->user->refresh();
        $this->assertEquals(5000, $this->user->available_coins);
        $this->assertEquals(5000, $this->user->lifetime_earned_coins);

        // 2. Request withdrawal of 3,000 Coins ($3.00 LTC)
        $wdLockTx = $this->ledgerService->lockWithdrawal(
            user: $this->user,
            coins: 3000,
            idempotencyKey: 'idem_wd_lock_3k',
            withdrawalId: 'wd_ltc_001',
            cryptoAsset: 'LTC'
        );

        $this->user->refresh();
        $this->assertEquals(2000, $this->user->available_coins);
        $this->assertEquals(3000, $this->user->locked_withdrawal_coins);
        $this->assertEquals(5000, $this->user->lifetime_earned_coins);

        // 3. Admin approves and sets tx_hash
        $this->ledgerService->finalizeWithdrawal(
            user: $this->user,
            coins: 3000,
            idempotencyKey: 'idem_wd_approve_3k',
            withdrawalId: 'wd_ltc_001',
            txHash: '0xabc123litecointxhash',
            parentTransactionId: $wdLockTx->id
        );

        $this->user->refresh();
        $this->assertEquals(2000, $this->user->available_coins);
        $this->assertEquals(0, $this->user->locked_withdrawal_coins);
        $this->assertEquals(5000, $this->user->lifetime_earned_coins);
    }

    public function test_lock_and_refund_crypto_withdrawal_flow(): void
    {
        // 1. Initial credit
        $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 4000,
            offerId: 'offer_task_1',
            idempotencyKey: 'idem_earning_refund_test'
        );

        // 2. Lock 2,000 Coins for withdrawal
        $wdLockTx = $this->ledgerService->lockWithdrawal(
            user: $this->user,
            coins: 2000,
            idempotencyKey: 'idem_wd_lock_refund',
            withdrawalId: 'wd_usdt_002',
            cryptoAsset: 'USDT'
        );

        $this->user->refresh();
        $this->assertEquals(2000, $this->user->available_coins);
        $this->assertEquals(2000, $this->user->locked_withdrawal_coins);

        // 3. Admin rejects withdrawal with reason
        $this->ledgerService->refundWithdrawal(
            user: $this->user,
            coins: 2000,
            idempotencyKey: 'idem_wd_reject_refund',
            withdrawalId: 'wd_usdt_002',
            reason: 'Invalid USDT TRC20 address checksum',
            parentTransactionId: $wdLockTx->id
        );

        $this->user->refresh();
        // Fully refunded back to available
        $this->assertEquals(4000, $this->user->available_coins);
        $this->assertEquals(0, $this->user->locked_withdrawal_coins);
        // Lifetime earned remains 4,000 (not inflated by the refund!)
        $this->assertEquals(4000, $this->user->lifetime_earned_coins);
    }

    public function test_compensating_chargeback_references_parent_transaction(): void
    {
        $originalTx = $this->ledgerService->creditOfferEarning(
            user: $this->user,
            coins: 1000,
            offerId: 'offer_chargeback_test',
            idempotencyKey: 'idem_orig_cb'
        );

        $this->user->refresh();
        $this->assertEquals(1000, $this->user->available_coins);

        // Postback reversal / chargeback
        $cbTx = $this->ledgerService->chargeback(
            user: $this->user,
            coins: 1000,
            idempotencyKey: 'idem_cb_reversal',
            parentTransactionId: $originalTx->id,
            reason: 'Advertiser flagged proxy conversion'
        );

        $this->assertEquals($originalTx->id, $cbTx->parent_transaction_id);

        $this->user->refresh();
        $this->assertEquals(0, $this->user->available_coins);
    }
}
