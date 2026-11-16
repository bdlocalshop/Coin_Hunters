<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $availableCoins = (int) $this->available_coins;
        $availableUsd = number_format($availableCoins / 1000, 2, '.', '');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'referral_code' => $this->referral_code,
            'available_coins' => $availableCoins,
            'pending_coins' => (int) $this->pending_coins,
            'locked_withdrawal_coins' => (int) $this->locked_withdrawal_coins,
            'lifetime_earned_coins' => (int) $this->lifetime_earned_coins,
            'available_usd' => $availableUsd,
            'status' => $this->status,
            'country_code' => $this->country_code,
            'fraud_score' => (int) $this->fraud_score,
            'signup_bonus_paid' => (bool) $this->signup_bonus_paid,
            'referral_bonus_paid' => (bool) $this->referral_bonus_paid,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
