<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Asset;
use App\Services\DataLoader\Importer\IteratorIterator;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;
use LogicException;

use function is_object;

class AssetsIteratorImporter extends AssetsImporter {
    /**
     * @var \App\Utils\Iterators\Contracts\ObjectIterator<\App\Services\DataLoader\Schema\ViewAsset>
     */
    private ObjectIterator $iterator;

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    /**
     * @param \App\Utils\Iterators\Contracts\ObjectIterator<string|\App\Models\Asset> $iterator
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
