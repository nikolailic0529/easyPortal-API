<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\DistributorFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\DistributorLoader;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use DateTimeInterface;

class DistributorsImporter extends Importer {
    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Parent
        parent::onBeforeChunk($items, $status);

        // Prefetch
        $this->container
            ->make(DistributorFactory::class)
            ->prefetch($items);
    }

    protected function makeIterator(DateTimeInterface $from = null): QueryIterator {
        return $this->client->getDistributors($from);
    }

    protected function makeLoader(): Loader {
        return $this->container->make(DistributorLoader::class);
    }

    protected function makeResolver(): Resolver {
        return $this->container->make(DistributorResolver::class);
    }

    protected function getObjectsCount(DateTimeInterface $from = null): ?int {
        return $from ? null : $this->client->getDistributorsCount();
    }
}
