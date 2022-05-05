<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Assets;

use App\Models\Asset;
use App\Services\DataLoader\Importer\IteratorIterator;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;
use LogicException;

use function is_object;

/**
 * @template TItem of \App\Services\DataLoader\Schema\ViewAsset
 * @template TChunkData of \App\Services\DataLoader\Collector\Data
 * @template TState of \App\Services\DataLoader\Importer\Importers\Assets\AbstractImporterState
 *
 * @extends Importer<TItem, TChunkData, TState>
 */
class IteratorImporter extends Importer {
    /**
     * @var ObjectIterator<ViewAsset>
     */
    private ObjectIterator $iterator;

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    /**
     * @param ObjectIterator<string|Asset> $iterator
     */
    public function setIterator(ObjectIterator $iterator): static {
        $this->iterator = new IteratorIterator(
            $this->getExceptionHandler(),
            $iterator,
            function (Asset|string $asset): ?ViewAsset {
                $asset = is_object($asset) ? $asset->getKey() : $asset;
                $asset = $this->isWithDocuments()
                    ? $this->getClient()->getAssetByIdWithDocuments($asset)
                    : $this->getClient()->getAssetById($asset);

                return $asset;
            },
        );

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function getTotal(State $state): ?int {
        return null;
    }

    protected function getIterator(State $state): ObjectIterator {
        if ($state->from !== null) {
            throw new LogicException('Parameter `from` is not supported.');
        }

        return $this->iterator;
    }
    // </editor-fold>
}
