<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Exceptions\DistributorNotFound;
use App\Services\DataLoader\Importer\Importers\Distributors\IteratorImporter;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\LoaderState;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\Contracts\Processor;
use Exception;

/**
 * @extends Loader<LoaderState>
 */
class DistributorLoader extends Loader {
    protected function getModelNotFoundException(string $id): Exception {
        return new DistributorNotFound($id);
    }

    /**
     * @inheritDoc
     */
    protected function operations(): array {
        return [
            new CompositeOperation(
                'Properties',
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
