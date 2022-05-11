<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Loader\CallbackLoader;
use App\Services\DataLoader\Loader\Concerns\WithDocuments;
use App\Services\DataLoader\Loader\Concerns\WithWarrantyCheck;
use App\Services\DataLoader\Loader\Loader;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\EmptyProcessor;
use App\Utils\Processor\State;
use Exception;

use function array_merge;

/**
 * @extends Loader<AssetLoaderState>
 */
class AssetLoader extends Loader {
    use WithDocuments;
    use WithWarrantyCheck;

    // <editor-fold desc="Loader">
    // =========================================================================
    protected function getModelNotFoundException(string $id): Exception {
        return new AssetNotFound($id);
    }

    /**
     * @inheritDoc
     */
    protected function getOperations(CompositeState $state): array {
        return [
            new CompositeOperation(
                'Warranty Check',
                function (AssetLoaderState $state): Processor {
                    if (!$state->withWarrantyCheck) {
                        return $this->getContainer()->make(EmptyProcessor::class);
                    }

                    return $this
                        ->getContainer()
                        ->make(CallbackLoader::class)
                        ->setObjectId($state->objectId)
                        ->setCallback(static function (Client $client, string $objectId): void {
                            $client->runAssetWarrantyCheck($objectId);
                        });
                },
            ),
            new CompositeOperation(
                'Updating properties',
                function (AssetLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setWithDocuments($state->withDocuments)
                        ->setIterator(new ObjectsIterator(
                            $this->getExceptionHandler(),
                            [$state->objectId],
                        ));
                },
                $this->getModelNotFoundHandler(),
            ),
        ];
    }
    //</editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new AssetLoaderState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'withWarrantyCheck' => $this->isWithWarrantyCheck(),
            'withDocuments'     => $this->isWithDocuments(),
        ]);
    }
    // </editor-fold>
}
