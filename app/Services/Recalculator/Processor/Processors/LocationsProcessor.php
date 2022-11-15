<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Data\Location;
use App\Models\LocationCustomer;
use App\Models\LocationReseller;
use App\Services\Recalculator\Processor\Processor;
use App\Utils\Processor\EloquentState;
use App\Utils\Processor\State;
use Illuminate\Support\Collection;

use function array_filter;
use function array_keys;
use function array_merge;
use function array_unique;
use function count;

use const SORT_REGULAR;

/**
 * @extends Processor<Location,LocationsChunkData,EloquentState<Location>>
 */
class LocationsProcessor extends Processor {
    protected function getModel(): string {
        return Location::class;
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $items = new Collection($items);
        $data  = new LocationsChunkData($items);

        return $data;
    }

    /**
     * @param EloquentState<Location> $state
     * @param LocationsChunkData      $data
     * @param Location                $item
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        // Prepare
        $location                 = $item;
        $locationAssets           = $data->getLocationAssetsCount($location);
        $locationResellers        = $data->getLocationResellers($location);
        $locationCustomers        = $data->getLocationCustomers($location);
        $locationAssetsByReseller = $data->getLocationAssetsByReseller($location);
        $locationAssetsByCustomer = $data->getLocationAssetsByCustomer($location);

        // Resellers
        /** @var Collection<string, LocationReseller> $resellers */
        $resellers = new Collection();
        $ids       = array_filter(array_unique(
            array_merge($locationResellers, array_keys($locationAssetsByReseller)),
            SORT_REGULAR,
        ));

        foreach ($ids as $id) {
            $reseller               = new LocationReseller();
            $reseller->assets_count = $locationAssetsByReseller[$id] ?? 0;
            $resellers[$id]         = $reseller;
        }

        $location->resellersPivots = $resellers;
        $location->save();

        // Customers
        /** @var Collection<string, LocationCustomer> $customers */
        $customers = new Collection();
        $ids       = array_filter(array_unique(
            array_merge($locationCustomers, array_keys($locationAssetsByCustomer)),
            SORT_REGULAR,
        ));

        foreach ($ids as $id) {
            $customer               = new LocationCustomer();
            $customer->assets_count = $locationAssetsByCustomer[$id] ?? 0;
            $customers[$id]         = $customer;
        }

        $location->customersPivots = $customers;
        $location->save();

        // Countable
        $location->customers_count = count($customers);
        $location->assets_count    = $locationAssets;
        $location->save();
    }
}
