<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Resellers;

use App\Services\DataLoader\Importer\Concerns\WithReseller;
use App\Services\DataLoader\Importer\Importers\Documents\AbstractImporter;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @extends AbstractImporter<DocumentsImporterState>
 */
class DocumentsImporter extends AbstractImporter {
    use WithReseller;

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getDocumentsByReseller($state->resellerId);
    }

    protected function getTotal(State $state): ?int {
        return null;
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new DocumentsImporterState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'resellerId' => $this->getResellerId(),
        ]);
    }
    // </editor-fold>
}
