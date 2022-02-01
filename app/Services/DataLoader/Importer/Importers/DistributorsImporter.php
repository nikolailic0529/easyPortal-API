<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Importer\Status;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\DistributorLoader;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Utils\Iterators\ObjectIterator;
use DateTimeInterface;

class DistributorsImporter extends Importer {
    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Parent
        parent::onBeforeChunk($items, $status);

        // Prefetch
        $data = new DistributorsImporterChunkData($items);

        $this->container
            ->make(DistributorResolver::class)
            ->prefetch($data->getDistributors());
    }

    protected function makeIterator(DateTimeInterface $from = null): ObjectIterator {
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
