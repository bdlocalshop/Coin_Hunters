<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Enums;

enum EntryDirection: string
{
    case DEBIT = 'DEBIT';
    case CREDIT = 'CREDIT';
}
