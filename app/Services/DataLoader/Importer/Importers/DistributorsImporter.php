<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Distributor;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Loader\Loader;
use App\Services\DataLoader\Loader\Loaders\DistributorLoader;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Processor\State;

class DistributorsImporter extends Importer {
    protected function register(): void {
        // empty
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data = new DistributorsImporterChunkData($items);

        $this->getContainer()
            ->make(DistributorResolver::class)
            ->prefetch($data->get(Distributor::class));

        return $data;
    }

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getDistributors($state->from);
    }

    protected function makeLoader(): Loader {
        return $this->getContainer()->make(DistributorLoader::class);
    }

    protected function makeResolver(): Resolver {
        return $this->getContainer()->make(DistributorResolver::class);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getDistributorsCount();
    }
}
