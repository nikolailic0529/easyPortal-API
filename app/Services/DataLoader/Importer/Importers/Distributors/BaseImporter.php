<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers\Distributors;

use App\Models\Distributor;
use App\Services\DataLoader\Factory\Factories\DistributorFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\DistributorResolver;
use App\Services\DataLoader\Schema\Company;
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
        $data = new BaseImporterChunkData($items);

        $this->getContainer()
            ->make(DistributorResolver::class)
            ->prefetch($data->get(Distributor::class));

        return $data;
    }

    protected function makeFactory(State $state): ModelFactory {
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
