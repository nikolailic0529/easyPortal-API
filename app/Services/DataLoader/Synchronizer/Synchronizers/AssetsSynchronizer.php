<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Synchronizer\Synchronizers;

use App\Models\Asset;
use App\Services\DataLoader\Importer\Importers\Assets\Importer;
use App\Services\DataLoader\Importer\Importers\Assets\IteratorImporter;
use App\Services\DataLoader\Synchronizer\Synchronizer;
use App\Services\DataLoader\Synchronizer\SynchronizerState;
use App\Utils\Iterators\Contracts\MixedIterator;
use App\Utils\Processor\Contracts\MixedProcessor;

/**
 * @extends Synchronizer<Asset>
 */
class AssetsSynchronizer extends Synchronizer {
    protected function getModel(): string {
        return Asset::class;
    }

    protected function getProcessor(SynchronizerState $state): MixedProcessor {
        return $this->getContainer()->make(Importer::class);
    }

    protected function getOutdatedProcessor(SynchronizerState $state, MixedIterator $iterator): MixedProcessor {
        return $this->getContainer()->make(IteratorImporter::class)->setIterator($iterator);
    }
}
