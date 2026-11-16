<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Payouts\Models\CryptoPayoutMethod;
use Illuminate\Database\Seeder;

class CryptoPayoutMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'symbol' => 'LTC',
                'name' => 'Litecoin (LTC)',
                'network' => 'Litecoin Core',
                'icon_url' => 'https://cryptologos.cc/logos/litecoin-ltc-logo.svg',
                'min_coins' => 1000, // $1.00 USD
                'fee_coins' => 0,
                'address_regex' => '^(L|M|ltc1)[a-km-zA-HJ-NP-Z1-9]{26,43}$',
                'is_active' => true,
            ],
            [
                'symbol' => 'USDT_TRC20',
                'name' => 'Tether USD (TRC20)',
                'network' => 'Tron (TRC-20)',
                'icon_url' => 'https://cryptologos.cc/logos/tether-usdt-logo.svg',
                'min_coins' => 5000, // $5.00 USD
                'fee_coins' => 0,
                'address_regex' => '^T[a-km-zA-HJ-NP-Z1-9]{33}$',
                'is_active' => true,
            ],
            [
                'symbol' => 'BTC',
                'name' => 'Bitcoin (BTC)',
                'network' => 'Bitcoin Network',
                'icon_url' => 'https://cryptologos.cc/logos/bitcoin-btc-logo.svg',
                'min_coins' => 15000, // $15.00 USD
                'fee_coins' => 1000, // $1.00 fee
                'address_regex' => '^(1|3|bc1)[a-km-zA-HJ-NP-Z1-9]{25,62}$',
                'is_active' => true,
            ],
            [
                'symbol' => 'SOL',
                'name' => 'Solana (SOL)',
                'network' => 'Solana Mainnet',
                'icon_url' => 'https://cryptologos.cc/logos/solana-sol-logo.svg',
                'min_coins' => 5000, // $5.00 USD
                'fee_coins' => 0,
                'address_regex' => '^[1-9A-HJ-NP-Za-km-z]{32,44}$',
                'is_active' => true,
            ],
        ];

        foreach ($methods as $method) {
            CryptoPayoutMethod::updateOrCreate(
                ['symbol' => $method['symbol']],
                $method
            );
        }
    }
}
