<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Customers;

use App\Models\Customer;
use App\Services\DataLoader\Processors\Importer\Concerns\WithIterator;
use App\Services\DataLoader\Schema\Types\Company;
use App\Utils\Processor\State;

/**
 * @extends BaseImporter<BaseImporterState>
 */
class IteratorImporter extends BaseImporter {
    /**
     * @use WithIterator<Customer, Company, BaseImporterState>
     */
    use WithIterator;

    /**
     * @param BaseImporterState $state
     *
     * @return Company|null
     */
    protected function getItem(State $state, string $item): mixed {
        return $this->getClient()->getCustomerById($item);
    }
}
