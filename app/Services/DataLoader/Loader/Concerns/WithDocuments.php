<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Models\Document;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Importer\Importers\Customers\DocumentsImporter as CustomerDocumentsImporter;
use App\Services\DataLoader\Importer\Importers\Documents\IteratorImporter;
use App\Services\DataLoader\Importer\Importers\Resellers\DocumentsImporter as ResellerDocumentsImporter;
use App\Services\DataLoader\Loader\CompanyLoaderState;
use App\Services\DataLoader\Loader\Loader;
use App\Utils\Iterators\Eloquent\EloquentIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\EmptyProcessor;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TState of \App\Services\DataLoader\Loader\CompanyLoaderState
 *
 * @mixin Loader
 */
trait WithDocuments {
    protected bool $withDocuments = false;

    abstract protected function getContainer(): Container;

    public function isWithDocuments(): bool {
        return $this->withDocuments;
    }

    public function setWithDocuments(bool $withDocuments): static {
        $this->withDocuments = $withDocuments;

        return $this;
    }

    /**
     * @return array<int, CompositeOperation<TState>>
     */
    protected function getDocumentsOperations(): array {
        return [
            new CompositeOperation(
                'Documents update',
                function (CompanyLoaderState $state): Processor {
                    if (!$state->withDocuments) {
                        return $this->getContainer()->make(EmptyProcessor::class);
                    }

                    return $this
                        ->getDocumentsImporter($state)
                        ->setObjectId($state->objectId);
                },
            ),
            new CompositeOperation(
                'Outdated Documents update',
                function (CompanyLoaderState $state): Processor {
                    if (!$state->withDocuments) {
                        return $this->getContainer()->make(EmptyProcessor::class);
                    }

                    $iterator  = $this->getMissedDocuments($state)->getChangeSafeIterator();
                    $iterator  = new EloquentIterator($iterator);
                    $processor = $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
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
