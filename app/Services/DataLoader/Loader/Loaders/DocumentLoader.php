<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Exceptions\DocumentNotFound;
use App\Services\DataLoader\Importer\Importers\Documents\IteratorImporter;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\LoaderState;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\CompositeState;
use App\Utils\Processor\Contracts\Processor;
use Exception;

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
    protected function operations(CompositeState $state): array {
        return [
            new CompositeOperation(
                'Updating properties',
                function (LoaderState $state): Processor {
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
        ];
    }
}
