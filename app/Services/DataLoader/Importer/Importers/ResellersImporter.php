<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Importers;

use App\Models\Reseller;
use App\Services\DataLoader\Factory\Factories\ResellerFactory;
use App\Services\DataLoader\Factory\ModelFactory;
use App\Services\DataLoader\Importer\Importer;
use App\Services\DataLoader\Importer\ImporterState;
use App\Services\DataLoader\Resolver\Resolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use App\Services\DataLoader\Schema\Company;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Importer<Company, ResellersImporterChunkData, ImporterState, Reseller>
 */
class ResellersImporter extends Importer {
    protected function register(): void {
        // empty
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $data      = new ResellersImporterChunkData($items);
        $contacts  = $this->getContainer()->make(ContactResolver::class);
        $locations = $this->getContainer()->make(LocationResolver::class);

        $this->getContainer()
            ->make(ResellerResolver::class)
            ->prefetch(
                $data->get(Reseller::class),
                static function (Collection $resellers) use ($locations, $contacts): void {
                    $resellers->loadMissing('locations.location');
                    $resellers->loadMissing('locations.types');
                    $resellers->loadMissing('contacts');
                    $resellers->loadMissing('kpi');

                    $locations->put($resellers->pluck('locations')->flatten()->pluck('location')->flatten());
                    $contacts->put($resellers->pluck('contacts')->flatten());
                },
            );

        (new Collection($contacts->getResolved()))->loadMissing('types');

        return $data;
    }

    protected function getIterator(State $state): ObjectIterator {
        return $this->getClient()->getResellers($state->from);
    }

    protected function makeFactory(State $state): ModelFactory {
        return $this->getContainer()->make(ResellerFactory::class);
    }

    protected function makeResolver(State $state): Resolver {
        return $this->getContainer()->make(ResellerResolver::class);
    }

    protected function getTotal(State $state): ?int {
        return $state->from ? null : $this->getClient()->getResellersCount();
    }
}
