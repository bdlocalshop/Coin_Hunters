<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Adapters;

use App\Domain\Offerwalls\DTOs\PostbackPayloadDTO;
use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Models\User;
use Illuminate\Http\Request;

interface OfferwallAdapterInterface
{
    /**
     * Unique identifier slug for this adapter.
     */
    public function getProviderSlug(): string;

    /**
     * Fetch and normalize direct campaigns from provider's catalog API.
     *
     * @param array<string, mixed> $filters
     * @return array<array<string, mixed>>
     */
    public function fetchCampaigns(OfferwallProvider $provider, array $filters = []): array;

    /**
     * Fetch live matched surveys targeted to a user.
     *
     * @param array<string, mixed> $params
     * @return array<array<string, mixed>>
     */
    public function fetchSurveys(OfferwallProvider $provider, User $user, array $params = []): array;

    /**
     * Verify cryptographic HMAC/hash signature or secret token on postback.
     */
    public function verifySignature(Request $request, OfferwallProvider $provider): bool;

    /**
     * Normalize incoming postback payload into standardized DTO.
     */
    public function parsePostback(Request $request, OfferwallProvider $provider): PostbackPayloadDTO;
}
