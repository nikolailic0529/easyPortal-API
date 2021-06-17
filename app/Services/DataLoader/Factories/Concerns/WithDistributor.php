<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factories\Concerns;

use App\Models\Distributor;
use App\Services\DataLoader\Exceptions\DistributorNotFoundException;
use App\Services\DataLoader\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\ViewDocument;

trait WithDistributor {
    abstract protected function getDistributorResolver(): DistributorResolver;

    protected function distributor(ViewDocument $object): ?Distributor {
        // Id
        $id = $object->distributorId ?? null;

        // Search
        $distributor = null;

        if ($id) {
            $distributor = $this->getDistributorResolver()->get($id);
        }

        // Found?
        if ($id && !$distributor) {
            throw new DistributorNotFoundException($id, $object);
        }

        // Return
        return $distributor;
    }
}
