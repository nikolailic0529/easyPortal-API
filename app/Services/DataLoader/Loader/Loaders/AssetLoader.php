<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Exceptions\AssetNotFound;
use App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Loader\Loader;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\CompositeOperation;
use App\Utils\Processor\Contracts\Processor;
use App\Utils\Processor\State;
use Exception;

use function array_merge;

/**
 * @extends Loader<AssetLoaderState>
 */
class AssetLoader extends Loader {
    protected bool $withDocuments = false;

    public function isWithDocuments(): bool {
        return $this->withDocuments;
    }

    public function setWithDocuments(bool $withDocuments): static {
        $this->withDocuments = $withDocuments;

        return $this;
    }

    // <editor-fold desc="Loader">
    // =========================================================================
    protected function getModelNotFoundException(string $id): Exception {
        return new AssetNotFound($id);
    }

    /**
     * @inheritDoc
     */
    protected function operations(): array {
        return [
            new CompositeOperation(
                'Asset update',
                function (AssetLoaderState $state): Processor {
                    return $this
                        ->getContainer()
                        ->make(IteratorImporter::class)
                        ->setIterator(new ObjectsIterator(
                            $this->getExceptionHandler(),
                            [$state->objectId],
                        ));
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
            'withDocuments' => $this->isWithDocuments(),
        ]);
    }
    // </editor-fold>
}
