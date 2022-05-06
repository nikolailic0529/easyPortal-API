<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Documents;

use App\Services\DataLoader\Importer\Concerns\WithIterator;
use App\Services\DataLoader\Schema\Document;
use App\Utils\Processor\State;

/**
 * @extends BaseImporter<BaseImporterState>
 */
class IteratorImporter extends BaseImporter {
    /**
     * @use WithIterator<\App\Models\Document, Document, BaseImporterState>
     */
    use WithIterator;

    protected function getTotal(State $state): ?int {
        return null;
    }

    /**
     * @param BaseImporterState $state
     *
     * @return Document|null
     */
    protected function getItem(State $state, string $item): mixed {
        return $this->getClient()->getDocumentById($item);
    }
}
