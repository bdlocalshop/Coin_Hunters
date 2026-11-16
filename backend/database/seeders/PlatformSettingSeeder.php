<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Fraud\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'signup_bonus_coins',
                'value' => '250',
                'description' => 'Coin bonus automatically credited to new verified users passing initial anti-fraud checks.',
            ],
            [
                'key' => 'referral_bonus_coins',
                'value' => '500',
                'description' => 'Flat one-time Coin bonus credited to a referrer once their referral reaches qualification threshold.',
            ],
            [
                'key' => 'referral_qualification_coins',
                'value' => '1000',
                'description' => 'Lifetime offer coins a referred user must earn before qualifying their referrer for the referral bonus.',
            ],
            [
                'key' => 'hold_threshold_coins',
                'value' => '5000',
                'description' => 'Minimum coin reward amount on an offer that triggers the high-value fraud review hold period.',
            ],
            [
                'key' => 'hold_duration_days',
                'value' => '14',
                'description' => 'Number of days high-value offer coins remain locked in pending status before releasing.',
            ],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
