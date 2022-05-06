<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Documents;

use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

/**
 * @extends AbstractImporter<AbstractImporterState>
 */
class Importer extends AbstractImporter {
    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getDocuments($state->from);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getDocumentsCount();
    }
}
