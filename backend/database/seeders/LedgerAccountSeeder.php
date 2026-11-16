<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Ledger\Enums\AccountCode;
use App\Domain\Ledger\Models\LedgerAccount;
use Illuminate\Database\Seeder;

class LedgerAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'code' => AccountCode::ASSET_NETWORK_RECEIVABLES->value,
                'name' => 'Network Receivables',
                'type' => 'asset',
                'description' => 'Gross revenue receivables from integrated offerwall and survey networks.',
            ],
            [
                'code' => AccountCode::LIABILITY_USER_AVAILABLE_BALANCE->value,
                'name' => 'User Available Balance',
                'type' => 'liability',
                'description' => 'Spendable coin liabilities owed to active users.',
            ],
            [
                'code' => AccountCode::LIABILITY_USER_PENDING_BALANCE->value,
                'name' => 'User Pending Balance',
                'type' => 'liability',
                'description' => 'Held coin liabilities subject to anti-fraud or chargeback hold periods.',
            ],
            [
                'code' => AccountCode::LIABILITY_USER_LOCKED_WITHDRAWAL->value,
                'name' => 'User Locked Withdrawal',
                'type' => 'liability',
                'description' => 'Escrowed coin liabilities committed to pending crypto withdrawal requests.',
            ],
            [
                'code' => AccountCode::EXPENSE_MARKETING_BONUSES->value,
                'name' => 'Marketing Bonuses',
                'type' => 'expense',
                'description' => 'Platform marketing expenses for user signups and qualifying referral rewards.',
            ],
            [
                'code' => AccountCode::ASSET_CRYPTO_TREASURY->value,
                'name' => 'Crypto Treasury',
                'type' => 'asset',
                'description' => 'Platform cryptocurrency treasury reserves disbursing user payouts.',
            ],
        ];

        foreach ($accounts as $account) {
            LedgerAccount::firstOrCreate(
                ['code' => $account['code']],
                $account
            );
        }
    }
}
