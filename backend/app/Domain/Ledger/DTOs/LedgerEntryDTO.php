<?php

declare(strict_types=1);

namespace App\Domain\Ledger\DTOs;

use App\Domain\Ledger\Enums\AccountCode;
use App\Domain\Ledger\Enums\EntryDirection;

readonly class LedgerEntryDTO
{
    public string $accountCode;
    public EntryDirection $direction;

    /**
     * @param AccountCode|string $account
     * @param EntryDirection|string $direction
     * @param int $amount Whole coins (must be > 0)
     * @param int|null $userId User ID associated with this entry (if user-level liability)
     */
    public function __construct(
        AccountCode|string $account,
        EntryDirection|string $direction,
        public int $amount,
        public ?int $userId = null,
    ) {
        $this->accountCode = $account instanceof AccountCode ? $account->value : $account;
        $this->direction = $direction instanceof EntryDirection ? $direction : EntryDirection::from($direction);
    }
}
