<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Enums;

enum TransactionType: string
{
    case OFFER_COMPLETION = 'offer_completion';
    case OFFER_PENDING = 'offer_pending';
    case PENDING_RELEASE = 'pending_release';
    case SIGNUP_BONUS = 'signup_bonus';
    case REFERRAL_BONUS = 'referral_bonus';
    case WITHDRAWAL_REQUEST = 'withdrawal_request';
    case WITHDRAWAL_APPROVED = 'withdrawal_approved';
    case WITHDRAWAL_REJECTED = 'withdrawal_rejected';
    case CHARGEBACK = 'chargeback';
    case MANUAL_ADJUSTMENT = 'manual_adjustment';

    /**
     * Determine if this transaction type represents external user earnings
     * that should increment lifetime_earned_coins.
     */
    public function isEarning(): bool
    {
        return match ($this) {
            self::OFFER_COMPLETION,
            self::OFFER_PENDING,
            self::SIGNUP_BONUS,
            self::REFERRAL_BONUS => true,
            default => false,
        };
    }
}
