<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Reseller;
use App\Services\DataLoader\Exceptions\ResellerNotFoundException;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Asset;

trait WithReseller {
    abstract protected function getResellerResolver(): ResellerResolver;

    protected function reseller(Asset $asset): ?Reseller {
        $id       = $asset->resellerId ?? null;
        $reseller = null;

        if ($id) {
            $reseller = $this->getResellerResolver()->get($id);
        }

        if ($id && !$reseller) {
            throw new ResellerNotFoundException($id, $asset);
        }

        return $reseller;
    }
}
