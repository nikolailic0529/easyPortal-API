<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Customers;

use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Collector\Data;
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
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<Company, BaseImporterChunkData, TState, Customer>
 */
abstract class BaseImporter extends Importer {
    protected function register(): void {
        $this->getContainer()->bindIf(ResellerFinder::class, ResellerLoaderFinder::class);
    }

    protected function preload(State $state, Data $data, Collection $models): void {
        // Prepare
        $contactsResolver  = $this->getContainer()->make(ContactResolver::class);
        $locationsResolver = $this->getContainer()->make(LocationResolver::class);

        // Customers
        $models->loadMissing('locations.location');
        $models->loadMissing('locations.types');
        $models->loadMissing('contacts.types');
        $models->loadMissing('kpi');
        $models->loadMissing('resellersPivots.kpi');

        $locationsResolver->add($models->pluck('locations')->flatten()->pluck('location')->flatten());
        $contactsResolver->add($models->pluck('contacts')->flatten());

        // Resellers
        $this->getContainer()
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class));
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
