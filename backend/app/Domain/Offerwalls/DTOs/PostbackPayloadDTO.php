<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\DTOs;

readonly class PostbackPayloadDTO
{
    /**
     * @param string $providerSlug
     * @param string $externalTransactionId
     * @param int $userId
     * @param int $payoutCoins
     * @param float $payoutUsd
     * @param bool $isReversal
     * @param string|null $clickUuid
     * @param string|null $offerId
     * @param string|null $reversalReason
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public string $providerSlug,
        public string $externalTransactionId,
        public int $userId,
        public int $payoutCoins,
        public float $payoutUsd = 0.0,
        public bool $isReversal = false,
        public ?string $clickUuid = null,
        public ?string $offerId = null,
        public ?string $reversalReason = null,
        public array $rawPayload = [],
    ) {}
}
