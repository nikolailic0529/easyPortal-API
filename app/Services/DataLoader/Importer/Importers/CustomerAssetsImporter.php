<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\Importer\Concerns\WithCustomer;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @template TItem of \App\Services\DataLoader\Schema\ViewAsset
 * @template TChunkData of \App\Services\DataLoader\Collector\Data
 * @template TState of \App\Services\DataLoader\Importer\Importers\CustomerAssetsImporterState
 *
 * @extends AssetsImporter<TItem, TChunkData, TState>
 */
class CustomerAssetsImporter extends AssetsImporter {
    use WithCustomer;

    // <editor-fold desc="Importer">
    // =========================================================================
    /**
     * @param CustomerAssetsImporterState $state
     */
    protected function getIterator(State $state): ObjectIterator {
        return $state->withDocuments
            ? $this->getClient()->getAssetsByCustomerIdWithDocuments($state->customerId, $state->from)
            : $this->getClient()->getAssetsByCustomerId($state->customerId, $state->from);
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
        return new CustomerAssetsImporterState($state);
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
