<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Resellers;

use App\Models\Reseller;
use App\Services\DataLoader\Processors\Importer\ImporterChunkData;
use App\Services\DataLoader\Schema\Types\Company;

class BaseImporterChunkData extends ImporterChunkData {
    public function collect(mixed $object): static {
        if ($object instanceof Company) {
            $this->add(Reseller::class, $object->id ?? null);
        } else {
            parent::collect($object);
        }

        return $this;
    }
}
