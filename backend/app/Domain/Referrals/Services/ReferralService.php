<?php

declare(strict_types=1);

namespace App\Domain\Referrals\Services;

use App\Domain\Fraud\Models\PlatformSetting;
use App\Domain\Ledger\Enums\TransactionType;
use App\Domain\Ledger\Models\LedgerTransaction;
use App\Domain\Ledger\Services\LedgerService;
use App\Models\User;
use Illuminate\Support\Str;

class ReferralService
{
    public function __construct(
        private readonly LedgerService $ledgerService
    ) {}

    /**
     * Check if a referred user has earned enough lifetime coins to trigger
     * the flat one-time qualification referral bonus for their referrer.
     */
    public function checkAndRewardQualification(User $referredUser): ?LedgerTransaction
    {
        // 1. Must have a valid referrer and not already have paid the bonus
        if ($referredUser->referred_by_id === null || $referredUser->referral_bonus_paid) {
            return null;
        }

        // 2. Fetch qualification threshold from settings
        $qualificationThreshold = PlatformSetting::getInt('referral_qualification_coins', 1000);

        if ($referredUser->lifetime_earned_coins < $qualificationThreshold) {
            return null;
        }

        // 3. Find and validate referrer account
        $referrer = User::find($referredUser->referred_by_id);
        if ($referrer === null || $referrer->status === 'banned') {
            return null;
        }

        $bonusCoins = PlatformSetting::getInt('referral_bonus_coins', 500);
        $idempotencyKey = sprintf('referral_bonus_referrer_%d_referred_%d', $referrer->id, $referredUser->id);

        $description = sprintf(
            'Flat one-time referral qualification bonus for referring %s (%d Coins)',
            $referredUser->username,
            $bonusCoins
        );

        $tx = $this->ledgerService->creditBonus(
            user: $referrer,
            coins: $bonusCoins,
            type: TransactionType::REFERRAL_BONUS,
            idempotencyKey: $idempotencyKey,
            description: $description,
            metadata: [
                'referrer_id' => $referrer->id,
                'referred_user_id' => $referredUser->id,
                'referred_username' => $referredUser->username,
                'qualification_coins' => $qualificationThreshold,
            ]
        );

        // Mark qualification bonus as paid
        $referredUser->referral_bonus_paid = true;
        $referredUser->save();

        return $tx;
    }

    /**
     * Generate a collision-free unique 8-character referral code.
     */
    public function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Lookup active referrer by referral code.
     */
    public function resolveReferrerByCode(?string $code): ?User
    {
        if (empty($code)) {
            return null;
        }

        return User::where('referral_code', strtoupper(trim($code)))
            ->where('status', '!=', 'banned')
            ->first();
    }

    /**
     * Get aggregate referral stats for dashboard.
     *
     * @return array<string, mixed>
     */
    public function getReferralStats(User $user): array
    {
        $totalReferrals = User::where('referred_by_id', $user->id)->count();
        $qualifiedReferrals = User::where('referred_by_id', $user->id)
            ->where('referral_bonus_paid', true)
            ->count();

        $bonusPerReferral = PlatformSetting::getInt('referral_bonus_coins', 500);
        $lifetimeReferralCoins = $qualifiedReferrals * $bonusPerReferral;

        $baseUrl = config('app.url', 'http://localhost:3000');

        return [
            'referral_code' => $user->referral_code,
            'referral_link' => rtrim($baseUrl, '/') . '/r/' . $user->referral_code,
            'total_referrals' => $totalReferrals,
            'qualified_referrals' => $qualifiedReferrals,
            'pending_referrals' => max(0, $totalReferrals - $qualifiedReferrals),
            'bonus_per_referral_coins' => $bonusPerReferral,
            'lifetime_referral_coins' => $lifetimeReferralCoins,
        ];
    }
}
