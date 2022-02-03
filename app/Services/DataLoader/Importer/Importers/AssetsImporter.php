<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Importer\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Loader\Concerns\AssetsPrefetch;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\AssetLoader;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Processor\State;

class AssetsImporter extends Importer {
    use AssetsPrefetch;

    protected function register(): void {
        $this->getContainer()->bind(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->getContainer()->bind(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->getContainer()->bind(CustomerFinder::class, CustomerLoaderFinder::class);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return $this->prefetchAssets($items);
    }

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getAssetsWithDocuments($state->from);
    }

    protected function makeLoader(): Loader {
        return $this->getContainer()->make(AssetLoader::class)->setWithDocuments(true);
    }

    protected function makeResolver(): Resolver {
        return $this->getContainer()->make(AssetResolver::class);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getAssetsCount();
    }
}
