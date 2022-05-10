<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Customers;

use App\Models\Customer;
use App\Services\DataLoader\Importer\ImporterChunkData;
use App\Services\DataLoader\Schema\Company;

class BaseImporterChunkData extends ImporterChunkData {
    public function collect(mixed $object): static {
        if ($object instanceof Company) {
            $this->add(Customer::class, $object->id ?? null);
        } else {
            parent::collect($object);
        }

        return $this;
    }
}
