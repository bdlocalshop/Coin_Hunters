<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Offerwalls\Models\Offer;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Domain\Offerwalls\Services\OfferwallRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncOffersCommand extends Command
{
    protected $signature = 'offers:sync {provider? : Specific provider slug to sync}';
    protected $description = 'Sync direct campaign offers from integrated offerwall networks into the local catalog';

    public function handle(OfferwallRegistry $registry): int
    {
        $targetSlug = $this->argument('provider');

        $query = OfferwallProvider::where('is_active', true);
        if ($targetSlug) {
            $query->where('slug', $targetSlug);
        }

        $providers = $query->get();

        if ($providers->isEmpty()) {
            $this->warn('No active offerwall providers found to sync.');
            return self::SUCCESS;
        }

        $totalSynced = 0;

        foreach ($providers as $provider) {
            if (!$registry->hasAdapter($provider->slug)) {
                $this->warn("Skipping [{$provider->slug}]: no adapter registered.");
                continue;
            }

            $adapter = $registry->getAdapter($provider->slug);
            $this->info("Syncing campaigns from [{$provider->name}]...");

            try {
                $campaigns = $adapter->fetchCampaigns($provider);

                foreach ($campaigns as $data) {
                    Offer::updateOrCreate(
                        [
                            'provider_slug' => $provider->slug,
                            'external_offer_id' => $data['external_offer_id'],
                        ],
                        [
                            'title' => $data['title'],
                            'description' => $data['description'] ?? null,
                            'requirements' => $data['requirements'] ?? null,
                            'icon_url' => $data['icon_url'] ?? null,
                            'network_payout_usd' => $data['network_payout_usd'] ?? 0.0,
                            'user_payout_coins' => $data['user_payout_coins'] ?? 0,
                            'device_targets' => $data['device_targets'] ?? ['android', 'ios'],
                            'countries' => $data['countries'] ?? ['ALL'],
                            'categories' => $data['categories'] ?? ['games'],
                            'click_url_template' => $data['click_url_template'],
                            'epc_score' => $data['epc_score'] ?? 0.0,
                            'is_featured' => $data['is_featured'] ?? false,
                            'is_active' => true,
                        ]
                    );

                    $totalSynced++;
                }

                $this->info("✓ Synced " . count($campaigns) . " campaigns from [{$provider->name}].");
            } catch (\Throwable $e) {
                $this->error("Failed to sync [{$provider->name}]: " . $e->getMessage());
            }
        }

        // Invalidate Redis catalog caches (with graceful fallback if Redis offline)
        try {
            Cache::forget('active_offers_all');
        } catch (\Throwable $e) {
            // Redis offline in local environment
        }
        $this->info("✓ Total campaigns synced: {$totalSynced}.");

        return self::SUCCESS;
    }
}
