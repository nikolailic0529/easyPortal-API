<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;

class DistributorsImporterChunkData extends ChunkData {
    protected function process(Type $item): void {
        if ($item instanceof Company) {
            $this->addDistributor($item->id ?? null);
        } else {
            parent::process($item);
        }
    }
}
