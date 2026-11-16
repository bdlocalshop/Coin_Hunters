<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            LedgerAccountSeeder::class,
            PlatformSettingSeeder::class,
            OfferwallProviderSeeder::class,
            CryptoPayoutMethodSeeder::class,
        ]);

        // Default Admin Hunter account for Filament Backoffice
        User::updateOrCreate(
            ['email' => 'admin@coinhunters.local'],
            [
                'name' => 'Coin Hunters Administrator',
                'username' => 'superadmin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'referral_code' => 'COINADMIN',
                'is_admin' => true,
                'status' => 'active',
                'country_code' => 'US',
                'fraud_score' => 0,
            ]
        );
    }
}
