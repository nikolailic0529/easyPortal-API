<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Importer\ImporterState;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\CustomerLoader;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Processor\State;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

class CustomersImporter extends Importer {
    protected function register(): void {
        $this->getContainer()->bind(ResellerFinder::class, ResellerLoaderFinder::class);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data      = new CustomersImporterChunkData($items);
        $contacts  = $this->getContainer()->make(ContactResolver::class);
        $locations = $this->getContainer()->make(LocationResolver::class);

        $this->getContainer()
            ->make(CustomerResolver::class)
            ->prefetch(
                $data->get(Customer::class),
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

        $this->getContainer()
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class));

        (new Collection($contacts->getResolved()))->loadMissing('types');

        return $data;
    }

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getCustomers($state->from);
    }

    protected function makeLoader(State $state): Loader {
        return $this->getContainer()->make(CustomerLoader::class);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(CustomerResolver::class);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getCustomersCount();
    }
}
