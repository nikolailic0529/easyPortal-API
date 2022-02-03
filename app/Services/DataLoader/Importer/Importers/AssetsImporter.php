<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Importer\Finders\CustomerLoaderFinder;
use App\Services\DataLoader\Importer\Finders\DistributorLoaderFinder;
use App\Services\DataLoader\Importer\Finders\ResellerLoaderFinder;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Loader\Concerns\AssetsPrefetch;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\AssetLoader;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @template TItem
 * @template TChunkData of \App\Services\DataLoader\Collector\Data
 * @template TState of \App\Services\DataLoader\Importer\Importers\AssetsImporterState
 *
 * @extends \App\Services\DataLoader\Importer\Importer<TItem, TChunkData, TState>
 */
class AssetsImporter extends Importer {
    use AssetsPrefetch;

    private bool $withDocuments = true;

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function isWithDocuments(): bool {
        return $this->withDocuments;
    }

    public function setWithDocuments(bool $withDocuments): static {
        $this->withDocuments = $withDocuments;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function register(): void {
        $this->getContainer()->bind(DistributorFinder::class, DistributorLoaderFinder::class);
        $this->getContainer()->bind(ResellerFinder::class, ResellerLoaderFinder::class);
        $this->getContainer()->bind(CustomerFinder::class, CustomerLoaderFinder::class);
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        return $this->prefetchAssets($items);
    }

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getAssetsWithDocuments($state->from);
    }

    protected function makeLoader(State $state): Loader {
        return $this->getContainer()->make(AssetLoader::class)->setWithDocuments($state->withDocuments);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(AssetResolver::class);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getAssetsCount();
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new AssetsImporterState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'withDocuments' => $this->isWithDocuments(),
        ]);
    }
    // </editor-fold>
}
