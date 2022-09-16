<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Synchronizer\Synchronizers;

use App\Models\Distributor;
use App\Services\DataLoader\Importer\Importers\Distributors\Importer;
use App\Services\DataLoader\Importer\Importers\Distributors\IteratorImporter;
use App\Services\DataLoader\Synchronizer\Synchronizer;
use App\Services\DataLoader\Synchronizer\SynchronizerState;
use App\Utils\Iterators\Contracts\MixedIterator;
use App\Utils\Processor\Contracts\MixedProcessor;

/**
 * @extends Synchronizer<Distributor>
 */
class DistributorsSynchronizer extends Synchronizer {
    protected function getModel(): string {
        return Distributor::class;
    }

    protected function getProcessor(SynchronizerState $state): MixedProcessor {
        return $this->getContainer()->make(Importer::class);
    }

    protected function getOutdatedProcessor(SynchronizerState $state, MixedIterator $iterator): MixedProcessor {
        return $this->getContainer()->make(IteratorImporter::class)->setIterator($iterator);
    }
}
