<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Services\DataLoader\Importer\Concerns\WithFrom;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

/**
 * @extends AbstractImporter<AbstractImporterState>
 */
class Importer extends AbstractImporter {
    use WithFrom;

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getCustomers($state->from);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getCustomersCount();
    }
}
