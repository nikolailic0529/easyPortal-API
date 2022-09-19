<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Synchronizer\Synchronizers;

use App\Models\Customer;
use App\Services\DataLoader\Importer\Importers\Customers\Importer;
use App\Services\DataLoader\Importer\Importers\Customers\IteratorImporter;
use App\Services\DataLoader\Synchronizer\Synchronizer;
use App\Services\DataLoader\Synchronizer\SynchronizerState;
use App\Utils\Iterators\Contracts\MixedIterator;
use App\Utils\Processor\Contracts\MixedProcessor;

/**
 * @extends Synchronizer<Customer>
 */
class CustomersSynchronizer extends Synchronizer {
    protected function getModel(): string {
        return Customer::class;
    }

    protected function getProcessor(SynchronizerState $state): MixedProcessor {
        return $this->getContainer()->make(Importer::class);
    }

    protected function getOutdatedProcessor(SynchronizerState $state, MixedIterator $iterator): MixedProcessor {
        return $this->getContainer()->make(IteratorImporter::class)->setIterator($iterator);
    }
}
