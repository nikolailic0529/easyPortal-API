<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Resellers;

use App\Models\Reseller;
use App\Services\DataLoader\Importer\Concerns\WithIterator;
use App\Services\DataLoader\Schema\Company;
use App\Utils\Processor\State;

/**
 * @extends BaseImporter<BaseImporterState>
 */
class IteratorImporter extends BaseImporter {
    /**
     * @use WithIterator<Reseller, Company, BaseImporterState>
     */
    use WithIterator;

    /**
     * @param BaseImporterState $state
     *
     * @return Company|null
     */
    protected function getItem(State $state, string $item): mixed {
        return $this->getClient()->getResellerById($item);
    }
}
