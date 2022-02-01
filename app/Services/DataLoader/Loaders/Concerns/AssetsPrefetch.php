<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Importers\ChunkData;
use App\Services\DataLoader\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolvers\ResellerResolver;
use Illuminate\Database\Eloquent\Collection;

trait AssetsPrefetch {
    abstract protected function getContainer(): Container;

    /**
     * @param array<mixed> $items
     */
    protected function prefetchAssets(array $items): void {
        $data      = new ChunkData($items);
        $container = $this->getContainer();
        $locations = $container->make(LocationResolver::class);
        $contacts  = $container->make(ContactResolver::class);

        $container
            ->make(AssetResolver::class)
            ->prefetch($data->getAssets(), static function (Collection $assets) use ($locations, $contacts): void {
                $assets->loadMissing('warranties.serviceLevels');
                $assets->loadMissing('warranties.document');
                $assets->loadMissing('contacts.types');
                $assets->loadMissing('location');
                $assets->loadMissing('tags');
                $assets->loadMissing('oem');

                $locations->add($assets->pluck('locations')->flatten());
                $contacts->add($assets->pluck('contacts')->flatten());
            });

        $container
            ->make(ResellerResolver::class)
            ->prefetch($data->getResellers(), static function (Collection $resellers) use ($locations): void {
                $resellers->loadMissing('locations.location');

                $locations->add($resellers->pluck('locations')->flatten()->pluck('location')->flatten());
            });

        $container
            ->make(CustomerResolver::class)
            ->prefetch($data->getCustomers(), static function (Collection $customers) use ($locations): void {
                $customers->loadMissing('locations.location');

                $locations->add($customers->pluck('locations')->flatten()->pluck('location')->flatten());
            });
    }
}
