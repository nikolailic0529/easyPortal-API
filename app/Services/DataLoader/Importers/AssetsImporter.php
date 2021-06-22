<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\AssetLoader;
use App\Services\DataLoader\Loaders\Concerns\CalculatedProperties;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\AssetResolver;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

class AssetsImporter extends Importer {
    use CalculatedProperties;

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
        $this->container
            ->make(AssetFactory::class)
            ->prefetch($items, true, static function (Collection $assets): void {
                $assets->loadMissing('documentEntries');
                $assets->loadMissing('warranties');
                $assets->loadMissing('warranties.services');
                $assets->loadMissing('contacts');
                $assets->loadMissing('tags');
            });

        $this->container
            ->make(ResellerFactory::class)
            ->prefetch($items, true, static function (Collection $assets): void {
                $assets->loadMissing('locations');
                $assets->loadMissing('contacts');
                $assets->loadMissing('contacts.types');
            });

        $this->container
            ->make(CustomerFactory::class)
            ->prefetch($items, true, static function (Collection $assets): void {
                $assets->loadMissing('locations');
                $assets->loadMissing('contacts');
                $assets->loadMissing('contacts.types');
            });
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
}
