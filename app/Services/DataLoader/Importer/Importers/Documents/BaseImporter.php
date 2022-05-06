<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Documents;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\DataLoader\Factory\Factories\DocumentFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Finders\AssetLoaderFinder;
use App\Services\DataLoader\Importer\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Importer\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Importer\ImporterChunkData;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\DocumentResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Document as SchemaDocument;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<SchemaDocument, ImporterChunkData, TState, Document>
 */
abstract class BaseImporter extends Importer {
    protected function register(): void {
        $this->getContainer()->bind(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->getContainer()->bind(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->getContainer()->bind(CustomerFinder::class, CustomerLoaderFinder::class);
        $this->getContainer()->bind(AssetFinder::class, AssetLoaderFinder::class);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data     = new ImporterChunkData($items);
        $contacts = $this->getContainer()->make(ContactResolver::class);

        $this->getContainer()
            ->make(DocumentResolver::class)
            ->prefetch($data->get(Document::class), static function (Collection $assets) use ($contacts): void {
                $assets->loadMissing('contacts');

                $contacts->put($assets->pluck('contacts')->flatten());
            });

        $this->getContainer()
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class), static function (Collection $resellers) use ($contacts): void {
                $resellers->loadMissing('contacts');

                $contacts->put($resellers->pluck('contacts')->flatten());
            });

        $this->getContainer()
            ->make(CustomerResolver::class)
            ->prefetch($data->get(Customer::class), static function (Collection $customers) use ($contacts): void {
                $customers->loadMissing('contacts');

                $contacts->put($customers->pluck('contacts')->flatten());
            });

        (new Collection($contacts->getResolved()))->loadMissing('types');

        return $data;
    }

    protected function makeFactory(State $state): ModelFactory {
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
