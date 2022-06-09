<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Models\Document;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\DataLoader\Exceptions\DocumentNotFound;
use App\Services\DataLoader\Importer\Importers\Documents\IteratorImporter;
use App\Services\DataLoader\Loader\CallbackLoader;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\LoaderState;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\Processor;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * @extends Loader<LoaderState>
 */
class DocumentLoader extends Loader {
    protected function getModelNotFoundException(string $id): Exception {
        return new DocumentNotFound($id);
    }

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
                        ->setIterator(new ObjectsIterator(
                            [$state->objectId],
                        ));
                },
                $this->getModelNotFoundHandler(),
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
