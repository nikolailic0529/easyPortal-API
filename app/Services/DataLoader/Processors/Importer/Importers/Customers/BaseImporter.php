<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Customers;

use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Factory\Factories\CustomerFactory;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Processors\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Types\Company;
use App\Utils\Processor\State;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<Company, BaseImporterChunkData, TState, Customer>
 */
abstract class BaseImporter extends Importer {
    protected function register(): void {
        $this->getContainer()->bind(ResellerFinder::class, ResellerLoaderFinder::class);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        // Prepare
        $data              = $this->makeData($items);
        $contactsResolver  = $this->getContainer()->make(ContactResolver::class);
        $locationsResolver = $this->getContainer()->make(LocationResolver::class);

        // Customers
        $customers = $this->getContainer()
            ->make(CustomerResolver::class)
            ->prefetch(
                $data->get(Customer::class),
            )
            ->getResolved();

        $customers->loadMissing('locations.location');
        $customers->loadMissing('locations.types');
        $customers->loadMissing('contacts.types');
        $customers->loadMissing('kpi');
        $customers->loadMissing('resellersPivots.kpi');

        $locationsResolver->add($customers->pluck('locations')->flatten()->pluck('location')->flatten());
        $contactsResolver->add($customers->pluck('contacts')->flatten());

        // Resellers
        $this->getContainer()
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class));

        // Return
        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function makeData(array $items): mixed {
        return new BaseImporterChunkData($items);
    }

    protected function makeFactory(State $state): Factory {
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
        return new BaseImporterState($state);
    }
    // </editor-fold>
}
