<?php

declare(strict_types=1);

namespace App\Domain\Fraud\DTOs;

readonly class FraudInspectionResult
{
    /**
     * @param bool $isAllowed
     * @param int $riskScore 0-100
     * @param bool $isProxy
     * @param bool $isVpn
     * @param bool $isTor
     * @param bool $isHosting
     * @param bool $isEmulator
     * @param string|null $countryCode
     * @param string|null $asn
     * @param array<string> $reasons
     */
    public function __construct(
        public bool $isAllowed,
        public int $riskScore = 0,
        public bool $isProxy = false,
        public bool $isVpn = false,
        public bool $isTor = false,
        public bool $isHosting = false,
        public bool $isEmulator = false,
        public ?string $countryCode = null,
        public ?string $asn = null,
        public array $reasons = [],
    ) {}
}
