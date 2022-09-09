<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Documents;

use App\Models\Document;
use App\Services\DataLoader\Importer\Concerns\WithIterator;
use App\Services\DataLoader\Schema\Document as DataLoaderDocument;
use App\Utils\Processor\State;

/**
 * @extends BaseImporter<BaseImporterState>
 */
class IteratorImporter extends BaseImporter {
    /**
     * @use WithIterator<Document, DataLoaderDocument, BaseImporterState>
     */
    use WithIterator;

    /**
     * @param BaseImporterState $state
     *
     * @return DataLoaderDocument|null
     */
    protected function getItem(State $state, string $item): mixed {
        return $this->getClient()->getDocumentById($item);
    }
}
