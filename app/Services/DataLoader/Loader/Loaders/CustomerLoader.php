<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Models\Asset;
use App\Models\Document;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\CustomerNotFound;
use App\Services\DataLoader\Importer\Importers\Customers\AssetsImporter;
use App\Services\DataLoader\Importer\Importers\Customers\DocumentsImporter;
use App\Services\DataLoader\Importer\Importers\Customers\IteratorImporter;
use App\Services\DataLoader\Loader\CallbackLoader;
use App\Services\DataLoader\Loader\CompanyLoader;
use App\Services\DataLoader\Loader\CompanyLoaderState;
use App\Services\DataLoader\Loader\Concerns\WithWarrantyCheck;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\EmptyProcessor;
use App\Utils\Processor\State;
use Exception;
use Illuminate\Database\Eloquent\Builder;

use function array_merge;

/**
 * @extends CompanyLoader<CompanyLoaderState>
 */
class CustomerLoader extends CompanyLoader {
    use WithWarrantyCheck;

    // <editor-fold desc="Loader">
    // =========================================================================
    protected function getModelNotFoundException(string $id): Exception {
        return new CustomerNotFound($id);
    }

    /**
     * @inheritDoc
     */
    protected function operations(): array {
        return [
            new CompositeOperation(
                'Warranty Check',
                function (CustomerLoaderState $state): Processor {
                    if (!$state->withWarrantyCheck) {
                        return $this->getContainer()->make(EmptyProcessor::class);
                    }

                    return $this
                        ->getContainer()
                        ->make(CallbackLoader::class)
                        ->setObjectId($state->objectId)
                        ->setCallback(static function (Client $client, string $objectId): void {
                            $client->runCustomerWarrantyCheck($objectId);
                        });
                },
            ),
            new CompositeOperation(
                'Updating properties',
                function (CustomerLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setIterator(new ObjectsIterator(
                            $this->getExceptionHandler(),
                            [$state->objectId],
                        ));
                },
                $this->getModelNotFoundHandler(),
            ),
            ...$this->getAssetsOperations(),
            ...$this->getDocumentsOperations(),
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
