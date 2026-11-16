<?php

declare(strict_types=1);

namespace App\Domain\Fraud\Exceptions;

use App\Domain\Fraud\DTOs\FraudInspectionResult;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FraudDetectedException extends HttpException
{
    public function __construct(
        public readonly FraudInspectionResult $result,
        string $message = 'Security check failed: suspicious network or device activity detected.'
    ) {
        parent::__construct(403, $message);
    }
}
