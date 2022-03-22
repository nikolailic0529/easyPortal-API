<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

use function array_merge;

/**
 * @template TItem of \App\Services\DataLoader\Schema\ViewAsset
 * @template TChunkData of \App\Services\DataLoader\Collector\Data
 * @template TState of \App\Services\DataLoader\Importer\Importers\ResellerAssetsImporterState
 *
 * @extends AssetsImporter<TItem, TChunkData, TState>
 */
class ResellerAssetsImporter extends AssetsImporter {
    private string $resellerId;

    // <editor-fold desc="Getters / Setters">
    // =========================================================================
    public function getResellerId(): string {
        return $this->resellerId;
    }

    public function setResellerId(string $resellerId): static {
        $this->resellerId = $resellerId;

        return $this;
    }
    // </editor-fold>

    // <editor-fold desc="Importer">
    // =========================================================================
    /**
     * @param ResellerAssetsImporterState $state
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
        return new ResellerAssetsImporterState($state);
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
