<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\Importer\Concerns\WithReseller;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;
use LogicException;

use function array_merge;

/**
 * @extends DocumentsImporter<ResellerDocumentsImporterState>
 */
class ResellerDocumentsImporter extends DocumentsImporter {
    use WithReseller;

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function getIterator(State $state): ObjectIterator {
        if ($state->from !== null) {
            throw new LogicException('Parameter `from` is not supported.');
        }

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
        return new ResellerDocumentsImporterState($state);
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
