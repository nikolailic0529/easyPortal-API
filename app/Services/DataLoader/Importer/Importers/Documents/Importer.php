<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Documents;

use App\Services\DataLoader\Importer\Concerns\WithFrom;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

/**
 * @extends BaseImporter<BaseImporterState>
 */
class Importer extends BaseImporter {
    use WithFrom;

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getDocuments($state->from);
    }

    protected function getTotal(State $state): ?int {
        return $this->getClient()->getDocumentsCount($state->from);
    }
}
