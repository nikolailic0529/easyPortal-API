<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Distributor;
use App\Services\DataLoader\Exceptions\DistributorNotFound;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\Types\Document;
use App\Services\DataLoader\Schema\Types\ViewDocument;

/**
 * @mixin Factory
 */
trait WithDistributor {
    abstract protected function getDistributorFinder(): ?DistributorFinder;

    abstract protected function getDistributorResolver(): DistributorResolver;

    protected function distributor(Document|ViewDocument $object): ?Distributor {
        // Id
        $id = $object->distributorId ?? null;

        // Search
        $distributor = null;

        if ($id) {
            $distributor = $this->getDistributorResolver()->get(
                $id,
                function (?Distributor $distributor) use ($id, $object): Distributor {
                    $distributor ??= $this->getDistributorFinder()?->find($id);

                    if (!$distributor) {
                        throw new DistributorNotFound($id, $object);
                    }

                    return $distributor;
                },
            );
        }

        // Return
        return $distributor;
    }
}
