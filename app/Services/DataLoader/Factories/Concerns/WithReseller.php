<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Asset;
use App\Services\DataLoader\Schema\AssetDocument;
use App\Services\DataLoader\Schema\Document;

trait WithReseller {
    abstract protected function getResellerResolver(): ResellerResolver;

    protected function reseller(Asset|Document|AssetDocument $object): ?Reseller {
        // Id
        $id = null;

        if ($object instanceof AssetDocument) {
            $id = $object->reseller->id ?? null;
        } else {
            $id = $object->resellerId ?? null;
        }

        // Search
        $reseller = null;

        if ($id) {
            $reseller = $this->getResellerResolver()->get($id);
        }

        // Found?
        if ($id && !$reseller) {
            throw new ResellerNotFoundException($id, $object);
        }

        // Return
        return $reseller;
    }
}
