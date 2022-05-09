<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Models\Asset;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Importer\Importers\Customers\AssetsImporter as CustomerAssetsImporter;
use App\Services\DataLoader\Importer\Importers\Resellers\AssetsImporter as ResellerAssetsImporter;
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
trait WithAssets {
    protected bool $withAssets          = false;
    protected bool $withAssetsDocuments = false;

    abstract protected function getContainer(): Container;

    public function isWithAssets(): bool {
        return $this->withAssets;
    }

    public function setWithAssets(bool $withAssets): static {
        $this->withAssets = $withAssets;

        return $this;
    }

    public function isWithAssetsDocuments(): bool {
        return $this->isWithAssets() && $this->withAssetsDocuments;
    }

    public function setWithAssetsDocuments(bool $withAssetsDocuments): static {
        $this->withAssetsDocuments = $withAssetsDocuments;

        return $this;
    }

    /**
     * @return array<int, CompositeOperation<TState>>
     */
    protected function getAssetsOperations(): array {
        return [
            new CompositeOperation(
                'Loading assets',
                function (CompanyLoaderState $state): Processor {
                    if (!$state->withAssets) {
                        return $this->getContainer()->make(EmptyProcessor::class);
                    }

                    return $this
                        ->getAssetsImporter($state)
                        ->setObjectId($state->objectId)
                        ->setWithDocuments($state->withAssetsDocuments);
                },
            ),
            new CompositeOperation(
                'Checking outdated assets',
                function (CompanyLoaderState $state): Processor {
                    if (!$state->withAssets) {
                        return $this->getContainer()->make(EmptyProcessor::class);
                    }

                    $iterator  = $this->getMissedAssets($state)->getChangeSafeIterator();
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
    abstract protected function getAssetsImporter(
        CompanyLoaderState $state,
    ): ResellerAssetsImporter|CustomerAssetsImporter;

    /**
     * @param TState $state
     *
     * @return Builder<Asset>
     */
    abstract protected function getMissedAssets(CompanyLoaderState $state): Builder;
}
