<?php

declare(strict_types=1);

namespace App\Domain\Offerwalls\Services;

use App\Domain\Offerwalls\Adapters\BitLabsAdapter;
use App\Domain\Offerwalls\Adapters\CpxResearchAdapter;
use App\Domain\Offerwalls\Adapters\LootablyAdapter;
use App\Domain\Offerwalls\Adapters\OfferwallAdapterInterface;
use App\Domain\Offerwalls\Adapters\OfferwallComAdapter;
use App\Domain\Offerwalls\Adapters\OfferwallGgAdapter;
use InvalidArgumentException;

class OfferwallRegistry
{
    /**
     * @var array<string, OfferwallAdapterInterface>
     */
    private array $adapters = [];

    public function __construct()
    {
        $this->register(new OfferwallGgAdapter());
        $this->register(new OfferwallComAdapter());
        $this->register(new LootablyAdapter());
        $this->register(new BitLabsAdapter());
        $this->register(new CpxResearchAdapter());
    }

    public function register(OfferwallAdapterInterface $adapter): void
    {
        $this->adapters[$adapter->getProviderSlug()] = $adapter;
    }

    public function getAdapter(string $slug): OfferwallAdapterInterface
    {
        if (!isset($this->adapters[$slug])) {
            throw new InvalidArgumentException("No offerwall adapter registered for provider [{$slug}].");
        }

        return $this->adapters[$slug];
    }

    public function hasAdapter(string $slug): bool
    {
        return isset($this->adapters[$slug]);
    }

    /**
     * @return array<string, OfferwallAdapterInterface>
     */
    public function getAll(): array
    {
        return $this->adapters;
    }
}
