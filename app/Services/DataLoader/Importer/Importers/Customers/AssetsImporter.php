<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Services\DataLoader\Importer\Concerns\WithFrom;
use App\Services\DataLoader\Importer\Concerns\WithObjectId;
use App\Services\DataLoader\Importer\Importers\Assets\BaseImporter;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @extends BaseImporter<AssetsImporterState>
 */
class AssetsImporter extends BaseImporter {
    use WithFrom;
    use WithObjectId;

    // <editor-fold desc="Importer">
    // =========================================================================
    protected function getIterator(State $state): ObjectIterator {
        return $state->withDocuments
            ? $this->getClient()->getAssetsByCustomerIdWithDocuments($state->customerId, $state->from)
            : $this->getClient()->getAssetsByCustomerId($state->customerId, $state->from);
    }

    protected function getTotal(State $state): ?int {
        return $this->getClient()->getAssetsByCustomerIdCount($state->customerId, $state->from);
    }
    // </editor-fold>

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new AssetsImporterState($state);
    }

    /**
     * @inheritDoc
     */
    protected function defaultState(array $state): array {
        return array_merge(parent::defaultState($state), [
            'customerId' => $this->getObjectId(),
        ]);
    }
    // </editor-fold>
}
