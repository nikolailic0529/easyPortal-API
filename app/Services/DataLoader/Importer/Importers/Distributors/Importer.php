<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Distributors;

use App\Models\Distributor;
use App\Services\DataLoader\Factory\Factories\DistributorFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\Concerns\WithFrom;
use App\Services\DataLoader\Importer\Importer as AbstractImporter;
use App\Services\DataLoader\Importer\ImporterState;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\Company;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;

/**
 * @extends AbstractImporter<Company, AbstractImporterChunkData, ImporterState, Distributor>
 */
class Importer extends AbstractImporter {
    use WithFrom;

    protected function register(): void {
        // empty
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data = new AbstractImporterChunkData($items);

        $this->getContainer()
            ->make(DistributorResolver::class)
            ->prefetch($data->get(Distributor::class));

        return $data;
    }

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getDistributors($state->from);
    }

    protected function makeFactory(State $state): ModelFactory {
        return $this->getContainer()->make(DistributorFactory::class);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(DistributorResolver::class);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getDistributorsCount();
    }
}
