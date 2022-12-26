<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Distributors;

use App\Models\Distributor;
use App\Services\DataLoader\Processors\Importer\ImporterChunkData;
use App\Services\DataLoader\Schema\Types\Company;

class BaseImporterChunkData extends ImporterChunkData {
    public function collect(mixed $object): static {
        if ($object instanceof Company) {
            $this->add(Distributor::class, $object->id ?? null);
        } else {
            parent::collect($object);
        }

        return $this;
    }
}
