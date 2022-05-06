<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Assets;

use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

/**
 * @extends AbstractImporter<AbstractImporterState>
 */
class Importer extends AbstractImporter {
    protected function getIterator(State $state): ObjectIterator {
        return $state->withDocuments
            ? $this->getClient()->getAssetsWithDocuments($state->from)
            : $this->getClient()->getAssets($state->from);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getAssetsCount();
    }
}
