<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Services\DataLoader\Importer\Concerns\WithCustomer;
use App\Services\DataLoader\Importer\Importers\Documents\Importer;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;
use LogicException;

use function array_merge;

/**
 * @extends Importer<DocumentsImporterState>
 */
class DocumentsImporter extends Importer {
    use WithCustomer;

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function getIterator(State $state): ObjectIterator {
        if ($state->from !== null) {
            throw new LogicException('Parameter `from` is not supported.');
        }

        return $this->getClient()->getDocumentsByCustomer($state->customerId);
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
            'customerId' => $this->getCustomerId(),
        ]);
    }
    // </editor-fold>
}
