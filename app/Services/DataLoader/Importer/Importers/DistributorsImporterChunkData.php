<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Distributor;
use App\Services\DataLoader\Importer\ImporterChunkData;
use App\Services\DataLoader\Schema\Company;

class DistributorsImporterChunkData extends ImporterChunkData {
    public function collect(mixed $object): void {
        if ($object instanceof Company) {
            $this->add(Distributor::class, $object->id ?? null);
        } else {
            parent::collect($object);
        }
    }
}
