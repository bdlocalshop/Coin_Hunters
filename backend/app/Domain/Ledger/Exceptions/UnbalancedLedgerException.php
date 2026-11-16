<?php

declare(strict_types=1);

namespace App\Domain\Ledger\Exceptions;

use DomainException;

class UnbalancedLedgerException extends DomainException
{
    public function __construct(string $message = 'Ledger transaction is unbalanced: total debits must strictly equal total credits.')
    {
        parent::__construct($message);
    }
}
