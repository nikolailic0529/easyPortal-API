<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Synchronizer\Synchronizers;

use App\Models\Document;
use App\Services\DataLoader\Importer\Importers\Documents\Importer;
use App\Services\DataLoader\Importer\Importers\Documents\IteratorImporter;
use App\Services\DataLoader\Synchronizer\Synchronizer;
use App\Services\DataLoader\Synchronizer\SynchronizerState;
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
        return $this->getContainer()->make(Importer::class);
    }

    protected function getOutdatedProcessor(SynchronizerState $state, MixedIterator $iterator): MixedProcessor {
        return $this->getContainer()->make(IteratorImporter::class)->setIterator($iterator);
    }
}
