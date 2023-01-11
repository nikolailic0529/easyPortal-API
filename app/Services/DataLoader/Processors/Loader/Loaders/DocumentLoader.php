<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Loaders;

use App\Models\Document;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Processors\Importer\Importers\Documents\IteratorImporter;
use App\Services\DataLoader\Processors\Loader\CallbackLoader;
use App\Services\DataLoader\Processors\Loader\Loader;
use App\Services\DataLoader\Processors\Loader\LoaderState;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * @extends Loader<LoaderState>
 */
class DocumentLoader extends Loader {
    /**
     * @inheritDoc
     */
    protected function getOperations(CompositeState $state): array {
        return [
            new CompositeOperation(
                'Update properties',
                function (LoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setForce($state->force)
                        ->setIterator(new ObjectsIterator([
                            Document::query()->whereKey($state->objectId)->first() ?? $state->objectId,
                        ]));
                },
            ),
            new CompositeOperation(
                'Recalculate',
                function (LoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(CallbackLoader::class)
                        ->setObjectId($state->objectId)
                        ->setCallback(static function (Dispatcher $dispatcher, Client $client, string $objectId): void {
                            $dispatcher->dispatch(new DataImported(
                                (new Data())->add(Document::class, $objectId),
                            ));
                        });
                },
            ),
        ];
    }
}
