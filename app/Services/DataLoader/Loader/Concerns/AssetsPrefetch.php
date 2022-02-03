<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Concerns;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Reseller;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Importer\ImporterChunkData;
use App\Services\DataLoader\Resolver\Resolvers\AssetResolver;
use App\Services\DataLoader\Resolver\Resolvers\ContactResolver;
use App\Services\DataLoader\Resolver\Resolvers\CustomerResolver;
use App\Services\DataLoader\Resolver\Resolvers\LocationResolver;
use App\Services\DataLoader\Resolver\Resolvers\ResellerResolver;
use Illuminate\Database\Eloquent\Collection;

trait AssetsPrefetch {
    abstract protected function getContainer(): Container;

    /**
     * @param array<mixed> $items
     */
    protected function prefetchAssets(array $items): ImporterChunkData {
        $data      = new ImporterChunkData($items);
        $container = $this->getContainer();
        $locations = $container->make(LocationResolver::class);
        $contacts  = $container->make(ContactResolver::class);

        $container
            ->make(AssetResolver::class)
            ->prefetch(
                $data->get(Asset::class),
                static function (Collection $assets) use ($locations, $contacts): void {
                    $assets->loadMissing('warranties.serviceLevels');
                    $assets->loadMissing('warranties.document');
                    $assets->loadMissing('contacts.types');
                    $assets->loadMissing('location');
                    $assets->loadMissing('tags');
                    $assets->loadMissing('oem');

                    $locations->put($assets->pluck('locations')->flatten());
                    $contacts->put($assets->pluck('contacts')->flatten());
                },
            );

        $container
            ->make(ResellerResolver::class)
            ->prefetch($data->get(Reseller::class), static function (Collection $resellers) use ($locations): void {
                $resellers->loadMissing('locations.location');

                $locations->put($resellers->pluck('locations')->flatten()->pluck('location')->flatten());
            });

        $container
            ->make(CustomerResolver::class)
            ->prefetch($data->get(Customer::class), static function (Collection $customers) use ($locations): void {
                $customers->loadMissing('locations.location');

                $locations->put($customers->pluck('locations')->flatten()->pluck('location')->flatten());
            });

        return $data;
    }
}
