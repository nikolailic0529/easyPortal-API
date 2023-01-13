<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Loaders;

use App\Models\Asset;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Processors\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Processors\Loader\CallbackLoader;
use App\Services\DataLoader\Processors\Loader\Concerns\WithWarrantyCheck;
use App\Services\DataLoader\Processors\Loader\Loader;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\State;
use Illuminate\Contracts\Events\Dispatcher;

use function array_merge;

/**
 * @extends Loader<AssetLoaderState>
 */
class AssetLoader extends Loader {
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
                function (AssetLoaderState $state): ?Processor {
                    if (!$state->withWarrantyCheck) {
                        return null;
                    }

                    return $this
                        ->getContainer()
                        ->make(CallbackLoader::class)
                        ->setObjectId($state->objectId)
                        ->setCallback(static function (Dispatcher $dispatcher, Client $client, string $objectId): void {
                            $client->runAssetWarrantyCheck($objectId);
                        });
                },
            ),
            new CompositeOperation(
                'Update properties',
                function (AssetLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setForce($state->force)
                        ->setIterator(new ObjectsIterator([
                            Asset::query()->whereKey($state->objectId)->first() ?? $state->objectId,
                        ]));
                },
            ),
            new CompositeOperation(
                'Recalculate',
                function (AssetLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(CallbackLoader::class)
                        ->setObjectId($state->objectId)
                        ->setCallback(static function (Dispatcher $dispatcher, Client $client, string $objectId): void {
                            $dispatcher->dispatch(new DataImported(
                                (new Data())->add(Asset::class, $objectId),
                            ));
                        });
                },
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
        ]);
    }
    // </editor-fold>
}
