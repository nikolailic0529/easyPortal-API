<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Synchronizer\Synchronizers;

use App\Models\Document;
use App\Services\DataLoader\Processors\Importer\Importers\Documents\Importer;
use App\Services\DataLoader\Processors\Importer\Importers\Documents\IteratorImporter;
use App\Services\DataLoader\Processors\Synchronizer\Synchronizer;
use App\Services\DataLoader\Processors\Synchronizer\SynchronizerState;
use App\Utils\Iterators\Contracts\MixedIterator;
use App\Utils\Processor\Contracts\MixedProcessor;

/**
 * @extends Synchronizer<Document>
 */
class DocumentsSynchronizer extends Synchronizer {
    protected function getModel(): string {
        return Document::class;
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
