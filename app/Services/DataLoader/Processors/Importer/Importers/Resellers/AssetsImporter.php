<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Resellers;

use App\Services\DataLoader\Processors\Concerns\WithFrom;
use App\Services\DataLoader\Processors\Concerns\WithObjectId;
use App\Services\DataLoader\Processors\Importer\Importers\Assets\BaseImporter;
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
        return $this->getClient()->getAssetsByResellerId($state->resellerId, $state->from);
    }

    protected function getTotal(State $state): ?int {
        return $this->getClient()->getAssetsByResellerIdCount($state->resellerId, $state->from);
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
            'resellerId' => $this->getObjectId(),
        ]);
    }
    // </editor-fold>
}
