<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Exceptions;

use DomainException;

class DuplicateTransactionException extends DomainException
{
    public function __construct(string $message = 'A ledger transaction with this idempotency key already exists.')
    {
        parent::__construct($message);
    }
}
