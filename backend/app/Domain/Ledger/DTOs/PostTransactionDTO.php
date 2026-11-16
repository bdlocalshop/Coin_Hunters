<?php

declare(strict_types=1);

namespace App\Domain\Ledger\DTOs;

use App\Domain\Ledger\Enums\TransactionType;

readonly class PostTransactionDTO
{
    public string $type;

    /**
     * @param TransactionType|string $type
     * @param string $description
     * @param string $idempotencyKey
     * @param array<LedgerEntryDTO> $entries
     * @param string|null $referenceType
     * @param string|null $referenceId
     * @param int|null $parentTransactionId
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        TransactionType|string $type,
        public string $description,
        public string $idempotencyKey,
        public array $entries,
        public ?string $referenceType = null,
        public ?string $referenceId = null,
        public ?int $parentTransactionId = null,
        public ?array $metadata = null,
    ) {
        $this->type = $type instanceof TransactionType ? $type->value : $type;
    }
}
