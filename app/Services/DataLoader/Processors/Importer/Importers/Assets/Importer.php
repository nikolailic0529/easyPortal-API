<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Assets;

use App\Services\DataLoader\Processors\Concerns\WithFrom;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

/**
 * @extends BaseImporter<BaseImporterState>
 */
class Importer extends BaseImporter {
    use WithFrom;

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getAssets($state->from);
    }

    protected function getTotal(State $state): ?int {
        return $this->getClient()->getAssetsCount($state->from);
    }
}
