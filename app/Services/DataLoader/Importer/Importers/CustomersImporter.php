<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Importer\Status;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\CustomerLoader;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Utils\Iterators\ObjectIterator;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

class CustomersImporter extends Importer {
    protected function onRegister(): void {
        parent::onRegister();

        $this->container->bind(ResellerFinder::class, ResellerLoaderFinder::class);
    }

    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Parent
        parent::onBeforeChunk($items, $status);

        // Prefetch
        $data      = new CustomersImporterChunkData($items);
        $contacts  = $this->container->make(ContactResolver::class);
        $locations = $this->container->make(LocationResolver::class);

        $this->container
            ->make(CustomerResolver::class)
            ->prefetch(
                $data->getCustomers(),
                static function (Collection $customers) use ($locations, $contacts): void {
                    $customers->loadMissing('locations.location');
                    $customers->loadMissing('locations.types');
                    $customers->loadMissing('contacts');
                    $customers->loadMissing('kpi');
                    $customers->loadMissing('resellersPivots.kpi');

                    $locations->put($customers->pluck('locations')->flatten()->pluck('location')->flatten());
                    $contacts->put($customers->pluck('contacts')->flatten());
                },
            );

        $this->container
            ->make(ResellerResolver::class)
            ->prefetch($data->getResellers());

        (new Collection($contacts->getResolved()))->loadMissing('types');
    }

    protected function makeIterator(DateTimeInterface $from = null): ObjectIterator {
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
