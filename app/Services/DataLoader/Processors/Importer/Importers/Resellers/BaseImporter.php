<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Importer\Importers\Resellers;

use App\Models\Reseller;
use App\Services\DataLoader\Factory\Factories\ResellerFactory;
use App\Services\DataLoader\Factory\Factory;
use App\Services\DataLoader\Processors\Importer\Importer;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Types\Company;
use App\Utils\Processor\State;

/**
 * @template TState of BaseImporterState
 *
 * @extends Importer<Company, BaseImporterChunkData, TState, Reseller>
 */
abstract class BaseImporter extends Importer {
    protected function register(): void {
        // empty
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        // Prepare
        $data              = $this->makeData($items);
        $contactsResolver  = $this->getContainer()->make(ContactResolver::class);
        $locationsResolver = $this->getContainer()->make(LocationResolver::class);

        // Resellers
        $resellers = $this->getContainer()
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class))
            ->getResolved();

        $resellers->loadMissing('locations.location');
        $resellers->loadMissing('locations.types');
        $resellers->loadMissing('contacts.types');
        $resellers->loadMissing('kpi');

        $locationsResolver->add($resellers->pluck('locations')->flatten()->pluck('location')->flatten());
        $contactsResolver->add($resellers->pluck('contacts')->flatten());

        // Return
        return $data;
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
