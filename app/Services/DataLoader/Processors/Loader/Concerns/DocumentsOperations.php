<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Concerns;

use App\Models\Document;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Processors\Importer\Importers\Customers\DocumentsImporter as CustomerDocumentsImporter;
use App\Services\DataLoader\Processors\Importer\Importers\Documents\IteratorImporter;
use App\Services\DataLoader\Processors\Importer\Importers\Resellers\DocumentsImporter as ResellerDocumentsImporter;
use App\Services\DataLoader\Processors\Loader\CompanyLoader;
use App\Services\DataLoader\Processors\Loader\CompanyLoaderState;
use App\Utils\Iterators\Eloquent\EloquentIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TState of CompanyLoaderState
 *
 * @mixin CompanyLoader<TState>
 */
trait DocumentsOperations {
    use WithDocuments;

    abstract protected function getContainer(): Container;

    /**
     * @return array<int, CompositeOperation<TState>>
     */
    protected function getDocumentsOperations(): array {
        return [
            new CompositeOperation(
                'Update documents',
                function (CompanyLoaderState $state): ?Processor {
                    if (!$state->withDocuments) {
                        return null;
                    }

                    return $this
                        ->getDocumentsImporter($state)
                        ->setObjectId($state->objectId)
                        ->setForce($state->force)
                        ->setFrom($state->from);
                },
            ),
            new CompositeOperation(
                'Update outdated documents',
                function (CompanyLoaderState $state): ?Processor {
                    if ($state->from !== null || !$state->withDocuments) {
                        return null;
                    }

                    $iterator  = $this->getMissedDocuments($state)->getChangeSafeIterator();
                    $iterator  = new EloquentIterator($iterator);
                    $processor = $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setForce($state->force)
                        ->setIterator($iterator);

                    return $processor;
                },
            ),
        ];
    }

    /**
     * @param TState $state
     */
    abstract protected function getDocumentsImporter(
        CompanyLoaderState $state,
    ): ResellerDocumentsImporter|CustomerDocumentsImporter;

    /**
     * @param TState $state
     *
     * @return Builder<Document>
     */
    abstract protected function getMissedDocuments(
        CompanyLoaderState $state,
    ): Builder;
}
