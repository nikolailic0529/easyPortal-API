<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Synchronizer\Synchronizers;

use App\Models\Distributor;
use App\Services\DataLoader\Processors\Importer\Importers\Distributors\Importer;
use App\Services\DataLoader\Processors\Importer\Importers\Distributors\IteratorImporter;
use App\Services\DataLoader\Processors\Synchronizer\Synchronizer;
use App\Services\DataLoader\Processors\Synchronizer\SynchronizerState;
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
        return $this->getContainer()->make(Importer::class)
            ->setForce($state->force)
            ->setFrom($state->from);
    }

    protected function getOutdatedProcessor(SynchronizerState $state, MixedIterator $iterator): MixedProcessor {
        return $this->getContainer()->make(IteratorImporter::class)
            ->setIterator($iterator)
            ->setForce($state->force);
    }
}
