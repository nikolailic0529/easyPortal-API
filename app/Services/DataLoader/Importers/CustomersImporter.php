<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\CustomerLoader;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\LocationResolver;
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
        $contacts  = $this->container->make(ContactResolver::class);
        $locations = $this->container->make(LocationResolver::class);

        $this->container
            ->make(CustomerFactory::class)
            ->prefetch($items, false, static function (Collection $customers) use ($locations, $contacts): void {
                $customers->loadMissing('locations.location');
                $customers->loadMissing('locations.types');
                $customers->loadMissing('contacts');
                $customers->loadMissing('kpi');

                $locations->add($customers->pluck('locations')->flatten()->pluck('location')->flatten());
                $contacts->add($customers->pluck('contacts')->flatten());
            });

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
