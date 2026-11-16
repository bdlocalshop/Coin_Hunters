<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Exceptions;

use InvalidArgumentException;

class InvalidLedgerAccountException extends InvalidArgumentException
{
    public function __construct(string $message = 'Invalid or non-existent ledger account.')
    {
        parent::__construct($message);
    }
}
