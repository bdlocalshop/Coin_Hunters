<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Offerwalls\Models\OfferwallProvider;
use Illuminate\Database\Seeder;

class OfferwallProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $providers = [
            [
                'slug' => 'offerwall_gg',
                'name' => 'Offerwall.gg',
                'logo_url' => 'https://offerwall.gg/assets/images/logo.png',
                'payout_margin_percent' => 70,
                'bonus_multiplier' => 1.15, // +15% Weekend promo
                'allowed_ips' => ['127.0.0.1', '::1', '104.21.0.0/16', '172.67.0.0/16'],
                'secret_key' => env('OFFERWALL_GG_SECRET', 'test_offerwall_gg_secret'),
                'api_key' => 'test_api_key_offerwall_gg',
                'app_id' => 'coinhunters_gg',
                'is_active' => true,
            ],
            [
                'slug' => 'offerwall_com',
                'name' => 'Offerwall.com',
                'logo_url' => 'https://offerwall.com/assets/logo.svg',
                'payout_margin_percent' => 70,
                'bonus_multiplier' => 1.00,
                'allowed_ips' => ['127.0.0.1', '::1', '54.160.0.0/16'],
                'secret_key' => env('OFFERWALL_COM_SECRET', 'test_offerwall_com_secret'),
                'api_key' => 'test_api_key_offerwall_com',
                'app_id' => 'coinhunters_com',
                'is_active' => true,
            ],
            [
                'slug' => 'lootably',
                'name' => 'Lootably',
                'logo_url' => 'https://lootably.com/logo.png',
                'payout_margin_percent' => 70,
                'bonus_multiplier' => 1.10, // +10% boost
                'allowed_ips' => ['127.0.0.1', '::1', '143.244.0.0/16'],
                'secret_key' => env('LOOTABLY_SECRET', 'test_lootably_secret'),
                'api_key' => 'test_api_key_lootably',
                'app_id' => 'coinhunters_loot',
                'is_active' => true,
            ],
            [
                'slug' => 'bitlabs',
                'name' => 'BitLabs',
                'logo_url' => 'https://bitlabs.ai/assets/logo.svg',
                'payout_margin_percent' => 75,
                'bonus_multiplier' => 1.00,
                'allowed_ips' => ['127.0.0.1', '::1', '35.156.0.0/16'],
                'secret_key' => env('BITLABS_SECRET', 'test_bitlabs_secret'),
                'api_key' => 'test_api_key_bitlabs',
                'app_id' => 'bitlabs_app_123',
                'is_active' => true,
            ],
            [
                'slug' => 'cpx_research',
                'name' => 'CPX Research',
                'logo_url' => 'https://cpx-research.com/assets/logo.svg',
                'payout_margin_percent' => 75,
                'bonus_multiplier' => 1.20, // +20% promo
                'allowed_ips' => ['127.0.0.1', '::1', '167.172.0.0/16'],
                'secret_key' => env('CPX_RESEARCH_SECRET', 'test_cpx_research_secret'),
                'api_key' => 'test_api_key_cpx',
                'app_id' => 'cpx_app_456',
                'is_active' => true,
            ],
        ];

        foreach ($providers as $provider) {
            OfferwallProvider::updateOrCreate(
                ['slug' => $provider['slug']],
                $provider
            );
        }
    }
}
