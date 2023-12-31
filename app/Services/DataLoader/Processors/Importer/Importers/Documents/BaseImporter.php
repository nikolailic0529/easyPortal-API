<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Documents;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Factory\Factories\DocumentFactory;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Processors\Finders\AssetLoaderFinder;
use App\Services\DataLoader\Processors\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Processors\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Processors\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Processors\Importer\ImporterChunkData;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Types\Document as SchemaDocument;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<SchemaDocument, ImporterChunkData, TState, Document>
 */
abstract class BaseImporter extends Importer {
    protected function register(): void {
        $this->getContainer()->bindIf(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->getContainer()->bindIf(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->getContainer()->bindIf(CustomerFinder::class, CustomerLoaderFinder::class);
        $this->getContainer()->bindIf(AssetFinder::class, AssetLoaderFinder::class);
    }

    protected function preload(State $state, Data $data, Collection $models): void {
        // Documents
        $models->loadMissing([
            'contacts.types',
            'statuses',
        ]);

        $this->getContainer()
            ->make(ContactResolver::class)
            ->add($models->pluck('contacts')->flatten());

        // Resellers
        $this->getContainer()
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class));

        // Customers
        $this->getContainer()
            ->make(CustomerResolver::class)
            ->prefetch($data->get(Customer::class));
    }

    /**
     * @inheritDoc
     */
    protected function makeData(array $items): mixed {
        return new ImporterChunkData($items);
    }

    protected function makeFactory(State $state): Factory {
        return $this->getContainer()->make(DocumentFactory::class);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(DocumentResolver::class);
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
