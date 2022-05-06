<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Assets;

use App\Services\DataLoader\Importer\Concerns\WithIterator;
use App\Services\DataLoader\Schema\ViewAsset;
use App\Utils\Processor\State;

/**
 * @extends AbstractImporter<AbstractImporterState>
 */
class IteratorImporter extends AbstractImporter {
    /**
     * @use WithIterator<\App\Models\Asset, ViewAsset, AbstractImporterState>
     */
    use WithIterator;

    protected function getTotal(State $state): ?int {
        return null;
    }

    /**
     * @param AbstractImporterState $state
     *
     * @return ViewAsset|null
     */
    protected function getItem(State $state, string $item): mixed {
        return $state->withDocuments
            ? $this->getClient()->getAssetByIdWithDocuments($item)
            : $this->getClient()->getAssetById($item);
    }
}
