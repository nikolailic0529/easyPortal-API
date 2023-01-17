<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Assets;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Factory\Factories\AssetFactory;
use App\Services\DataLoader\Factory\Factory;
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
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Types\ViewAsset;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<ViewAsset, ImporterChunkData, TState, Asset>
 */
abstract class BaseImporter extends Importer {
    // <editor-fold desc="Importer">
    // =========================================================================
    protected function register(): void {
        $this->getContainer()->bindIf(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->getContainer()->bindIf(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->getContainer()->bindIf(CustomerFinder::class, CustomerLoaderFinder::class);
    }

    protected function preload(State $state, Data $data, Collection $models): void {
        // Prepare
        $container         = $this->getContainer();
        $documentsResolver = $container->make(DocumentResolver::class);
        $locationsResolver = $container->make(LocationResolver::class);
        $contactsResolver  = $container->make(ContactResolver::class);

        // Assets
        $models->loadMissing([
            'warranties.document',
            'contacts.types',
            'location',
            'tags',
            'coverages',
        ]);

        $documentsResolver->add($models->pluck('warranties')->flatten()->pluck('document')->flatten());
        $locationsResolver->add($models->pluck('location')->flatten());
        $contactsResolver->add($models->pluck('contacts')->flatten());

        // Resellers
        $container
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class));

        // Customers
        $container
            ->make(CustomerResolver::class)
            ->prefetch($data->get(Customer::class));

        // Documents
        $documents = $documentsResolver
            ->prefetch($data->get(Document::class))
            ->getResolved();

        $documents->loadMissing('statuses');
    }

    /**
     * @inheritDoc
     */
    protected function makeData(array $items): mixed {
        return new ImporterChunkData($items);
    }

    protected function makeFactory(State $state): Factory {
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
