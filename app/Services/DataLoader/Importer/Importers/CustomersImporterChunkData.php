<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Services\DataLoader\ChunkData;
use App\Services\DataLoader\Schema\Company;
use App\Services\DataLoader\Schema\Type;

class CustomersImporterChunkData extends ChunkData {
    protected function process(Type $item): void {
        if ($item instanceof Company) {
            $this->addCustomer($item->id ?? null);
        } else {
            parent::process($item);
        }
    }
}
