<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Documents;

use App\Services\DataLoader\Importer\Concerns\WithIterator;
use App\Services\DataLoader\Schema\Document;
use App\Utils\Processor\State;

/**
 * @extends AbstractImporter<AbstractImporterState>
 */
class IteratorImporter extends AbstractImporter {
    /**
     * @use WithIterator<\App\Models\Document, Document, AbstractImporterState>
     */
    use WithIterator;

    protected function getTotal(State $state): ?int {
        return null;
    }

    /**
     * @param AbstractImporterState $state
     *
     * @return Document|null
     */
    protected function getItem(State $state, string $item): mixed {
        return $this->getClient()->getDocumentById($item);
    }
}
