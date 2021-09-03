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
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolvers\LocationResolver;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

class AssetsImporter extends Importer {
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
        $contacts  = $this->container->make(ContactResolver::class);
        $locations = $this->container->make(LocationResolver::class);

        $this->container
            ->make(AssetFactory::class)
            ->prefetch($items, false, static function (Collection $assets) use ($locations, $contacts): void {
                $assets->loadMissing('warranties.serviceLevels');
                $assets->loadMissing('contacts');
                $assets->loadMissing('location');
                $assets->loadMissing('tags');
                $assets->loadMissing('oem');

                $locations->add($assets->pluck('locations')->flatten());
                $contacts->add($assets->pluck('contacts')->flatten());
            });

        $this->container
            ->make(ResellerFactory::class)
            ->prefetch($items, false, static function (Collection $resellers) use ($locations, $contacts): void {
                $resellers->loadMissing('locations');
                $resellers->loadMissing('contacts');

                $locations->add($resellers->pluck('locations')->flatten());
                $contacts->add($resellers->pluck('contacts')->flatten());
            });

        $this->container
            ->make(CustomerFactory::class)
            ->prefetch($items, false, static function (Collection $customers) use ($locations, $contacts): void {
                $customers->loadMissing('locations');
                $customers->loadMissing('contacts');

                $locations->add($customers->pluck('locations')->flatten());
                $contacts->add($customers->pluck('contacts')->flatten());
            });

        (new Collection($locations->getResolved()))->loadMissing('types');
        (new Collection($contacts->getResolved()))->loadMissing('types');
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
