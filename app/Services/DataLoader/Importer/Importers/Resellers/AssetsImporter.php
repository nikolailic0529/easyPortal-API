<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Resellers;

use App\Services\DataLoader\Importer\Concerns\WithReseller;
use App\Services\DataLoader\Importer\Importers\Assets\Importer;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @template TItem of \App\Services\DataLoader\Schema\ViewAsset
 * @template TChunkData of \App\Services\DataLoader\Collector\Data
 * @template TState of \App\Services\DataLoader\Importer\Importers\Resellers\AssetsImporterState
 *
 * @extends Importer<TItem, TChunkData, TState>
 */
class AssetsImporter extends Importer {
    use WithReseller;

    // <editor-fold desc="Importer">
    // =========================================================================
    /**
     * @param AssetsImporterState $state
     *
     * @return ObjectIterator<TItem>
     */
    protected function getIterator(State $state): ObjectIterator {
        return $state->withDocuments
            ? $this->getClient()->getAssetsByResellerIdWithDocuments($state->resellerId, $state->from)
            : $this->getClient()->getAssetsByResellerId($state->resellerId, $state->from);
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
        return new AssetsImporterState($state);
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
