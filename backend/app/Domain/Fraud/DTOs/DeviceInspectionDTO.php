<?php

declare(strict_types=1);

namespace App\Domain\Fraud\DTOs;

readonly class DeviceInspectionDTO
{
    public function __construct(
        public string $ipAddress,
        public string $deviceHash,
        public ?string $userAgent = null,
        public bool $isWebdriver = false,
        public bool $isEmulator = false,
        public ?string $screenResolution = null,
        public ?string $timezone = null,
    ) {}
}
