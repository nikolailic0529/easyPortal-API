<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\CustomerLoader;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use DateTimeInterface;

class CustomersImporter extends Importer {
    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Parent
        parent::onBeforeChunk($items, $status);

        // Prefetch
        $this->container
            ->make(CustomerFactory::class)
            ->prefetch($items);
    }

    protected function makeIterator(DateTimeInterface $from = null): QueryIterator {
        return $this->client->getCustomers($from);
    }

    protected function makeLoader(): Loader {
        return $this->container->make(CustomerLoader::class);
    }

    protected function makeResolver(): Resolver {
        return $this->container->make(CustomerResolver::class);
    }

    protected function getObjectsCount(DateTimeInterface $from = null): ?int {
        return $from ? null : $this->client->getCustomersCount();
    }
}
