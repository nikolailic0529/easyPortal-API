<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\AssetLoader;
use App\Services\DataLoader\Loaders\Concerns\AssetsPrefetch;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Utils\Iterators\QueryIterator;
use DateTimeInterface;

class AssetsImporter extends Importer {
    use AssetsPrefetch;

    protected function onRegister(): void {
        parent::onRegister();

        $this->container->bind(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->container->bind(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->container->bind(CustomerFinder::class, CustomerLoaderFinder::class);
    }

    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Parent
        parent::onBeforeChunk($items, $status);

        // Prefetch
        $this->prefetchAssets($items);
    }

    protected function makeIterator(DateTimeInterface $from = null): QueryIterator {
        return $this->client->getAssetsWithDocuments($from);
    }

    protected function makeLoader(): Loader {
        return $this->container->make(AssetLoader::class)->setWithDocuments(true);
    }

    protected function makeResolver(): Resolver {
        return $this->container->make(AssetResolver::class);
    }

    protected function getObjectsCount(DateTimeInterface $from = null): ?int {
        return $from ? null : $this->client->getAssetsCount();
    }
}
