<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Exceptions;

use RuntimeException;

class ImmutableRecordException extends RuntimeException
{
    public function __construct(string $message = 'Ledger records are strictly append-only and cannot be updated or deleted.')
    {
        parent::__construct($message);
    }
}
