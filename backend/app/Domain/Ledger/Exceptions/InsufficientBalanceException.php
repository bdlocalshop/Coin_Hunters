<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Exceptions;

use DomainException;

class InsufficientBalanceException extends DomainException
{
    public function __construct(string $message = 'Insufficient coin balance to execute this ledger transaction.')
    {
        parent::__construct($message);
    }
}
