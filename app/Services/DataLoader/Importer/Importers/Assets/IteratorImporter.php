<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Assets;

use App\Models\Asset;
use App\Services\DataLoader\Importer\Concerns\WithIterator;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Utils\Processor\State;

/**
 * @extends BaseImporter<BaseImporterState>
 */
class IteratorImporter extends BaseImporter {
    /**
     * @use WithIterator<Asset, ViewAsset, BaseImporterState>
     */
    use WithIterator;

    /**
     * @param BaseImporterState $state
     *
     * @return ViewAsset|null
     */
    protected function getItem(State $state, string $item): mixed {
        return $state->withDocuments
            ? $this->getClient()->getAssetByIdWithDocuments($item)
            : $this->getClient()->getAssetById($item);
    }
}
