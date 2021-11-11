<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loaders\Concerns;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Factories\AssetFactory;
use App\Services\DataLoader\Factories\CustomerFactory;
use App\Services\DataLoader\Factories\ResellerFactory;
use App\Services\DataLoader\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolvers\LocationResolver;
use Illuminate\Database\Eloquent\Collection;

trait AssetsPrefetch {
    abstract protected function getContainer(): Container;

    /**
     * @param array<mixed> $items
     */
    protected function assetsPrefetch(array $items): void {
        $container = $this->getContainer();
        $locations = $container->make(LocationResolver::class);
        $contacts  = $container->make(ContactResolver::class);

        $container
            ->make(AssetFactory::class)
            ->prefetch($items, false, static function (Collection $assets) use ($locations, $contacts): void {
                $assets->loadMissing('warranties.serviceLevels');
                $assets->loadMissing('warranties.document');
                $assets->loadMissing('contacts');
                $assets->loadMissing('location');
                $assets->loadMissing('tags');
                $assets->loadMissing('oem');

                $locations->add($assets->pluck('locations')->flatten());
                $contacts->add($assets->pluck('contacts')->flatten());
            });

        $container
            ->make(ResellerFactory::class)
            ->prefetch($items, false, static function (Collection $resellers) use ($locations, $contacts): void {
                $resellers->loadMissing('locations.location');
                $resellers->loadMissing('locations.types');
                $resellers->loadMissing('contacts');

                $locations->add($resellers->pluck('locations')->flatten()->pluck('location')->flatten());
                $contacts->add($resellers->pluck('contacts')->flatten());
            });

        $container
            ->make(CustomerFactory::class)
            ->prefetch($items, false, static function (Collection $customers) use ($locations, $contacts): void {
                $customers->loadMissing('locations.location');
                $customers->loadMissing('locations.types');
                $customers->loadMissing('contacts');

                $locations->add($customers->pluck('locations')->flatten()->pluck('location')->flatten());
                $contacts->add($customers->pluck('contacts')->flatten());
            });

        (new Collection($contacts->getResolved()))->loadMissing('types');
    }
}
