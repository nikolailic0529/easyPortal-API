<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Resellers;

use App\Models\Reseller;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Factory\Factories\ResellerFactory;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Types\Company;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<Company, BaseImporterChunkData, TState, Reseller>
 */
abstract class BaseImporter extends Importer {
    protected function register(): void {
        // empty
    }

    protected function preload(State $state, Data $data, Collection $models): void {
        $models->loadMissing('locations.location');
        $models->loadMissing('locations.types');
        $models->loadMissing('contacts.types');
        $models->loadMissing('kpi');

        $this->getContainer()
            ->make(LocationResolver::class)
            ->add(
                $models->pluck('locations')->flatten()->pluck('location')->flatten(),
            );
        $this->getContainer()
            ->make(ContactResolver::class)
            ->add(
                $models->pluck('contacts')->flatten(),
            );
    }

    /**
     * @inheritDoc
     */
    protected function makeData(array $items): mixed {
        return new BaseImporterChunkData($items);
    }

    protected function makeFactory(State $state): Factory {
        return $this->getContainer()->make(ResellerFactory::class);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(ResellerResolver::class);
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
