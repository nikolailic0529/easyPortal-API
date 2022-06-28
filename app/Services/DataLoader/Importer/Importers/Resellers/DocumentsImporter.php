<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Resellers;

use App\Services\DataLoader\Importer\Concerns\WithFrom;
use App\Services\DataLoader\Importer\Concerns\WithObjectId;
use App\Services\DataLoader\Importer\Importers\Documents\BaseImporter;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @extends BaseImporter<DocumentsImporterState>
 */
class DocumentsImporter extends BaseImporter {
    use WithFrom;
    use WithObjectId;

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getDocumentsByReseller($state->resellerId, $state->from);
    }

    protected function getTotal(State $state): ?int {
        return $this->getClient()->getDocumentsByResellerCount($state->resellerId, $state->from);
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
            'resellerId' => $this->getObjectId(),
        ]);
    }
    // </editor-fold>
}
