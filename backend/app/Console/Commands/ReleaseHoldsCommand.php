<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ledger\Services\LedgerService;
use App\Domain\Offerwalls\Models\OfferHold;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReleaseHoldsCommand extends Command
{
    protected $signature = 'ledger:release-holds';
    protected $description = 'Release expired high-value offer hold balances from pending to available user coins';

    public function handle(LedgerService $ledgerService): int
    {
        $expiredHolds = OfferHold::where('status', 'pending')
            ->where('release_at', '<=', now())
            ->with('user')
            ->get();

        if ($expiredHolds->isEmpty()) {
            $this->info('No expired offer holds pending release.');
            return self::SUCCESS;
        }

        $releasedCount = 0;

        foreach ($expiredHolds as $hold) {
            $user = $hold->user;
            if (!$user || $user->status === 'banned') {
                $this->warn("Skipping hold [{$hold->id}] for user [{$hold->user_id}] (invalid or banned).");
                continue;
            }

            try {
                $idempotencyKey = sprintf('release_hold_%d_tx_%d', $hold->id, $hold->ledger_transaction_id);

                $ledgerService->releasePendingCoins(
                    user: $user,
                    coins: $hold->coins,
                    idempotencyKey: $idempotencyKey,
                    parentTransactionId: $hold->ledger_transaction_id,
                    description: sprintf(
                        'Scheduled auto-release of held high-value offer reward (%d Coins)',
                        $hold->coins
                    )
                );

                $hold->status = 'released';
                $hold->save();

                $releasedCount++;
            } catch (\Throwable $e) {
                $this->error("Failed to release hold [{$hold->id}]: " . $e->getMessage());
                Log::error("Hold release failed for hold {$hold->id}: " . $e->getMessage());
            }
        }

        $this->info("✓ Successfully released {$releasedCount} expired offer holds.");
        return self::SUCCESS;
    }
}
