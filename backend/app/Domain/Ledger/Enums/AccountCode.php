<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Enums;

enum AccountCode: string
{
    case ASSET_NETWORK_RECEIVABLES = 'asset:network_receivables';
    case LIABILITY_USER_AVAILABLE_BALANCE = 'liability:user_available_balance';
    case LIABILITY_USER_PENDING_BALANCE = 'liability:user_pending_balance';
    case LIABILITY_USER_LOCKED_WITHDRAWAL = 'liability:user_locked_withdrawal';
    case EXPENSE_MARKETING_BONUSES = 'expense:marketing_bonuses';
    case ASSET_CRYPTO_TREASURY = 'asset:crypto_treasury';
}
