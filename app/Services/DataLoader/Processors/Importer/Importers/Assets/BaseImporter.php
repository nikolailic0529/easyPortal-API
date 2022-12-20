<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Assets;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Factory\Factories\AssetFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Processors\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Processors\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Processors\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Processors\Importer\ImporterChunkData;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Utils\Processor\State;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<ViewAsset, ImporterChunkData, TState, Asset>
 */
abstract class BaseImporter extends Importer {
    // <editor-fold desc="Importer">
    // =========================================================================
    protected function register(): void {
        $this->getContainer()->bind(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->getContainer()->bind(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->getContainer()->bind(CustomerFinder::class, CustomerLoaderFinder::class);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        // Prepare
        $data              = $this->makeData($items);
        $container         = $this->getContainer();
        $locationsResolver = $container->make(LocationResolver::class);
        $contactsResolver  = $container->make(ContactResolver::class);

        // Assets
        $assets = $container
            ->make(AssetResolver::class)
            ->prefetch($data->get(Asset::class))
            ->getResolved();

        $assets->loadMissing('warranties');
        $assets->loadMissing('contacts.types');
        $assets->loadMissing('location');
        $assets->loadMissing('tags');
        $assets->loadMissing('coverages');

        $locationsResolver->add($assets->pluck('locations')->flatten());
        $contactsResolver->add($assets->pluck('contacts')->flatten());

        // Resellers
        $resellers = $container
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class))
            ->getResolved();

        $resellers->loadMissing('locations.location');
        $locationsResolver->add($resellers->pluck('locations')->flatten()->pluck('location')->flatten());

        // Customers
        $customers = $container
            ->make(CustomerResolver::class)
            ->prefetch($data->get(Customer::class))
            ->getResolved();

        $customers->loadMissing('locations.location');
        $locationsResolver->add($customers->pluck('locations')->flatten()->pluck('location')->flatten());

        // Return
        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function makeData(array $items): mixed {
        return new ImporterChunkData($items);
    }

    protected function makeFactory(State $state): ModelFactory {
        return $this->getContainer()->make(AssetFactory::class);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(AssetResolver::class);
    }
    // </editor-fold>

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
