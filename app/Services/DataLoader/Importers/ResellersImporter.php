<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importers;

use App\Services\DataLoader\Client\QueryIterator;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Loader;
use App\Services\DataLoader\Loaders\ResellerLoader;
use App\Services\DataLoader\Resolver;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

class ResellersImporter extends Importer {
    /**
     * @param array<mixed> $items
     */
    protected function onBeforeChunk(array $items, Status $status): void {
        // Parent
        parent::onBeforeChunk($items, $status);

        // Prefetch
        $contacts  = $this->container->make(ContactResolver::class);
        $locations = $this->container->make(LocationResolver::class);

        $this->container
            ->make(ResellerFactory::class)
            ->prefetch($items, false, static function (Collection $resellers) use ($locations, $contacts): void {
                $resellers->loadMissing('locations.location');
                $resellers->loadMissing('locations.types');
                $resellers->loadMissing('contacts');
                $resellers->loadMissing('kpi');

                $locations->add($resellers->pluck('locations')->flatten()->pluck('location')->flatten());
                $contacts->add($resellers->pluck('contacts')->flatten());
            });

        (new Collection($contacts->getResolved()))->loadMissing('types');
    }

    protected function makeIterator(DateTimeInterface $from = null): QueryIterator {
        return $this->client->getResellers($from);
    }

    protected function makeLoader(): Loader {
        return $this->container->make(ResellerLoader::class);
    }

    protected function makeResolver(): Resolver {
        return $this->container->make(ResellerResolver::class);
    }

    protected function getObjectsCount(DateTimeInterface $from = null): ?int {
        return $from ? null : $this->client->getResellersCount();
    }
}
