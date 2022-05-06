<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Factory\Factories\CustomerFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Company;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TState of AbstractImporterState
 *
 * @extends Importer<Company, AbstractImporterChunkData, TState, Customer>
 */
abstract class AbstractImporter extends Importer {
    protected function register(): void {
        $this->getContainer()->bind(ResellerFinder::class, ResellerLoaderFinder::class);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data      = new AbstractImporterChunkData($items);
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

    protected function makeFactory(State $state): ModelFactory {
        return $this->getContainer()->make(CustomerFactory::class);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(CustomerResolver::class);
    }

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new AbstractImporterState($state);
    }
    // </editor-fold>
}
