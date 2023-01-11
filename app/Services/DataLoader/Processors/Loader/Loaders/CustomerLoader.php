<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Loaders;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Processors\Importer\Importers\Customers\AssetsImporter;
use App\Services\DataLoader\Processors\Importer\Importers\Customers\DocumentsImporter;
use App\Services\DataLoader\Processors\Importer\Importers\Customers\IteratorImporter;
use App\Services\DataLoader\Processors\Loader\CallbackLoader;
use App\Services\DataLoader\Processors\Loader\CompanyLoader;
use App\Services\DataLoader\Processors\Loader\CompanyLoaderState;
use App\Services\DataLoader\Processors\Loader\Concerns\WithWarrantyCheck;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\State;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Builder;

use function array_merge;

/**
 * @extends CompanyLoader<CompanyLoaderState>
 */
class CustomerLoader extends CompanyLoader {
    use WithWarrantyCheck;

    // <editor-fold desc="Loader">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function getOperations(CompositeState $state): array {
        return [
            new CompositeOperation(
                'Warranty Check',
                function (CustomerLoaderState $state): ?Processor {
                    if (!$state->withWarrantyCheck) {
                        return null;
                    }

                    return $this
                        ->getContainer()
                        ->make(CallbackLoader::class)
                        ->setObjectId($state->objectId)
                        ->setCallback(static function (Dispatcher $dispatcher, Client $client, string $objectId): void {
                            $client->runCustomerWarrantyCheck($objectId);
                        });
                },
            ),
            new CompositeOperation(
                'Update properties',
                function (CustomerLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setForce($state->force)
                        ->setIterator(new ObjectsIterator([
                            Customer::query()->whereKey($state->objectId)->first() ?? $state->objectId,
                        ]));
                },
            ),
            ...$this->getAssetsOperations(),
            ...$this->getDocumentsOperations(),
            new CompositeOperation(
                'Recalculate',
                function (CustomerLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(CallbackLoader::class)
                        ->setObjectId($state->objectId)
                        ->setCallback(static function (Dispatcher $dispatcher, Client $client, string $objectId): void {
                            $dispatcher->dispatch(new DataImported(
                                (new Data())->add(Customer::class, $objectId),
                            ));
                        });
                },
            ),
        ];
    }
    // </editor-fold>

    // <editor-fold desc="WithAssets">
    // =========================================================================
    protected function getAssetsImporter(CompanyLoaderState $state): AssetsImporter {
        return $this->getContainer()->make(AssetsImporter::class);
    }

    protected function getMissedAssets(CompanyLoaderState $state): Builder {
        return Asset::query()
            ->where('customer_id', '=', $state->objectId)
            ->where('synced_at', '<', $state->started);
    }
    // </editor-fold>

    // <editor-fold desc="WithDocuments">
    // =========================================================================
    protected function getDocumentsImporter(CompanyLoaderState $state): DocumentsImporter {
        return $this->getContainer()->make(DocumentsImporter::class);
    }

    protected function getMissedDocuments(CompanyLoaderState $state): Builder {
        return Document::query()
            ->where('customer_id', '=', $state->objectId)
            ->where('synced_at', '<', $state->started);
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new CustomerLoaderState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'withWarrantyCheck' => $this->isWithWarrantyCheck(),
        ]);
    }
    // </editor-fold>
}
