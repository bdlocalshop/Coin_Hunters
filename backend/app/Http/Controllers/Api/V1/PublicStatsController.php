<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ledger\Models\LedgerEntry;
use App\Domain\Ledger\Models\LedgerTransaction;
use App\Domain\Payouts\Models\WithdrawalRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicStatsController extends Controller
{
    /**
     * Get live platform metrics and recent live earnings ticker.
     */
    public function stats(): JsonResponse
    {
        $fetchStats = function () {
            $totalWithdrawnUsd = (float) WithdrawalRequest::where('status', 'approved')->sum('usd_amount') + 128450.00;
            $activeUsersCount = User::where('status', 'active')->count() + 1420;

            // Fetch recent 20 earnings for ticker
            $recentTransactions = LedgerTransaction::whereIn('type', ['offer_completion', 'withdrawal_approved', 'signup_bonus', 'referral_bonus'])
                ->with(['entries.user' => fn ($q) => $q->select('id', 'username', 'avatar_url')])
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            $ticker = [];
            foreach ($recentTransactions as $tx) {
                $userEntry = $tx->entries->first(fn (LedgerEntry $e) => $e->user_id !== null);
                $user = $userEntry?->user;

                if ($user) {
                    $amount = $userEntry->amount;
                    $typeLabel = match ($tx->type) {
                        'withdrawal_approved' => 'Crypto Payout',
                        'offer_completion' => 'Offer Reward',
                        'signup_bonus' => 'Signup Bonus',
                        'referral_bonus' => 'Referral Bonus',
                        default => 'Earnings',
                    };

                    $ticker[] = [
                        'id' => $tx->id,
                        'username' => $this->maskUsername($user->username),
                        'avatar_url' => $user->avatar_url,
                        'activity' => $typeLabel,
                        'description' => $tx->description,
                        'coins' => $amount,
                        'usd' => number_format($amount / 1000, 2),
                        'time_ago' => $tx->created_at?->diffForHumans(),
                    ];
                }
            }

            // Fallback sample ticker items if transactions empty
            if (empty($ticker)) {
                $samples = [
                    ['username' => 'Al***9', 'activity' => 'Raid: Shadow Legends', 'coins' => 31050, 'usd' => '31.05'],
                    ['username' => 'Cr***x', 'activity' => 'BitLabs Survey', 'coins' => 2450, 'usd' => '2.45'],
                    ['username' => 'Sa***a', 'activity' => 'Monopoly GO!', 'coins' => 19665, 'usd' => '19.66'],
                    ['username' => 'Vn***r', 'activity' => 'CPX Research', 'coins' => 1800, 'usd' => '1.80'],
                    ['username' => 'Da***k', 'activity' => 'LTC Payout', 'coins' => 50000, 'usd' => '50.00'],
                ];
                foreach ($samples as $i => $s) {
                    $ticker[] = [
                        'id' => $i + 1,
                        'username' => $s['username'],
                        'avatar_url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=' . urlencode($s['username']),
                        'activity' => $s['activity'],
                        'description' => "Completed {$s['activity']}",
                        'coins' => $s['coins'],
                        'usd' => $s['usd'],
                        'time_ago' => ($i * 2 + 1) . 'm ago',
                    ];
                }
            }

            return [
                'metrics' => [
                    'avg_time_to_cashout' => '14.2 Hours',
                    'avg_daily_earnings' => '$8.65 USD',
                    'total_usd_paid_out' => '$' . number_format($totalWithdrawnUsd, 2),
                    'total_active_users' => number_format($activeUsersCount),
                ],
                'ticker' => $ticker,
            ];
        };

        try {
            $data = Cache::remember('public_platform_stats', 30, $fetchStats);
        } catch (\Throwable) {
            $data = $fetchStats();
        }

        return response()->json($data);
    }

    /**
     * Get top platform earners for leaderboard.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $topUsers = User::where('status', 'active')
            ->orderByDesc('lifetime_earned_coins')
            ->limit(25)
            ->get(['id', 'username', 'avatar_url', 'country_code', 'lifetime_earned_coins', 'created_at']);

        $rank = 1;
        $leaderboard = $topUsers->map(function (User $u) use (&$rank) {
            $coins = (int) $u->lifetime_earned_coins;
            return [
                'rank' => $rank++,
                'username' => $u->username,
                'avatar_url' => $u->avatar_url,
                'country_code' => $u->country_code ?? 'US',
                'lifetime_earned_coins' => $coins,
                'lifetime_usd' => number_format($coins / 1000, 2),
            ];
        });

        return response()->json([
            'leaderboard' => $leaderboard,
        ]);
    }

    private function maskUsername(string $username): string
    {
        $len = strlen($username);
        if ($len <= 3) {
            return $username;
        }

        return substr($username, 0, 2) . '***' . substr($username, -1);
    }
}
