<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Concerns;

use App\Models\Asset;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Processors\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Processors\Importer\Importers\Customers\AssetsImporter as CustomerAssetsImporter;
use App\Services\DataLoader\Processors\Importer\Importers\Resellers\AssetsImporter as ResellerAssetsImporter;
use App\Services\DataLoader\Processors\Loader\CompanyLoaderState;
use App\Services\DataLoader\Processors\Loader\Loader;
use App\Utils\Iterators\Eloquent\EloquentIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TState of CompanyLoaderState
 *
 * @mixin Loader
 */
trait WithAssets {
    protected bool $withAssets = false;

    abstract protected function getContainer(): Container;

    public function isWithAssets(): bool {
        return $this->withAssets;
    }

    public function setWithAssets(bool $withAssets): static {
        $this->withAssets = $withAssets;

        return $this;
    }

    /**
     * @return array<int, CompositeOperation<TState>>
     */
    protected function getAssetsOperations(): array {
        return [
            new CompositeOperation(
                'Update assets',
                function (CompanyLoaderState $state): ?Processor {
                    if (!$state->withAssets) {
                        return null;
                    }

                    return $this
                        ->getAssetsImporter($state)
                        ->setObjectId($state->objectId)
                        ->setForce($state->force)
                        ->setFrom($state->from);
                },
            ),
            new CompositeOperation(
                'Update outdated assets',
                function (CompanyLoaderState $state): ?Processor {
                    if ($state->from !== null || !$state->withAssets) {
                        return null;
                    }

                    $iterator  = $this->getMissedAssets($state)->getChangeSafeIterator();
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
