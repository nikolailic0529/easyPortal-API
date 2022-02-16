<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Factory\Concerns;

use App\Models\Distributor;
use App\Services\DataLoader\Exceptions\DistributorNotFound;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\Document;
use App\Services\DataLoader\Schema\ViewDocument;

/**
 * @mixin \App\Services\DataLoader\Factory\Factory
 */
trait WithDistributor {
    abstract protected function getNormalizer(): Normalizer;

    abstract protected function getDistributorFinder(): ?DistributorFinder;

    abstract protected function getDistributorResolver(): DistributorResolver;

    protected function distributor(Document|ViewDocument $object): ?Distributor {
        // Id
        $id = $object->distributorId ?? null;

        // Search
        $distributor = null;

        if ($id) {
            $id          = $this->getNormalizer()->uuid($id);
            $distributor = $this->getDistributorResolver()->get($id, $this->factory(
                function () use ($id): ?Distributor {
                    return $this->getDistributorFinder()?->find($id);
                },
            ));
        }

        // Found?
        if ($id && !$distributor) {
            throw new DistributorNotFound($id, $object);
        }

        // Return
        return $distributor;
    }
}