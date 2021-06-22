<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\ResellerLoader;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use DateTimeInterface;

class ResellersImporter extends Importer {
    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Parent
        parent::onBeforeChunk($items, $status);

        // Prefetch
        $this->container
            ->make(ResellerFactory::class)
            ->prefetch($items);
    }

    protected function makeIterator(DateTimeInterface $from = null): QueryIterator {
        return $this->client->getResellers($from);
    }

    protected function makeLoader(): Loader {
        return $this->container->make(ResellerLoader::class);
    }

    protected function makeResolver(): Resolver {
        return $this->container->make(ResellerResolver::class);
    }
}
