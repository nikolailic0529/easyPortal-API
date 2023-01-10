<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Distributors;

use App\Models\Distributor;
use App\Services\DataLoader\Factory\Factories\DistributorFactory;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\Types\Company;
use App\Utils\Processor\State;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<Company, BaseImporterChunkData, TState, Distributor>
 */
abstract class BaseImporter extends Importer {
    protected function register(): void {
        // empty
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data = $this->makeData($items);

        $this->getContainer()
            ->make(DistributorResolver::class)
            ->prefetch($data->get(Distributor::class));

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function makeData(array $items): mixed {
        return new BaseImporterChunkData($items);
    }

    protected function makeFactory(State $state): Factory {
        return $this->getContainer()->make(DistributorFactory::class);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(DistributorResolver::class);
    }

    // <editor-fold desc="State">
    // =========================================================================
    /**
     * @inheritDoc
     */
    protected function restoreState(array $state): State {
        return new BaseImporterState($state);
    }
    // </editor-fold>
}
