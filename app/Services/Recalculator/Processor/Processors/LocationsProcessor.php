<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Location;
use App\Models\LocationCustomer;
use App\Models\LocationReseller;
use App\Services\Recalculator\Processor\Processor;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

use function array_filter;
use function array_keys;
use function array_merge;
use function array_unique;
use function count;

use const SORT_REGULAR;

/**
 * @extends \App\Services\Recalculator\Processor\Processor<\App\Models\Location,\App\Services\Recalculator\Processor\Processors\LocationsChunkData>
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
     * @param \App\Utils\Processor\EloquentState                                 $state
     * @param \App\Services\Recalculator\Processor\Processors\LocationsChunkData $data
     * @param \App\Models\Customer                                               $item
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        // Prepare
        $locationResellers        = $data->getLocationResellers($item);
        $locationCustomers        = $data->getLocationCustomers($item);
        $locationAssetsByReseller = $data->getLocationResellersAssets($item);
        $locationAssetsByCustomer = $data->getLocationCustomersAssets($item);
        $locationAssetsByLocation = $data->getLocationAssetsCount($item);

        // Resellers
        $resellers = [];
        $ids       = array_filter(array_unique(
            array_merge($locationResellers, array_keys($locationAssetsByReseller)),
            SORT_REGULAR,
        ));

        foreach ($ids as $id) {
            $resellers[$id]               = new LocationReseller();
            $resellers[$id]->assets_count = $locationAssetsByReseller[$id] ?? 0;
        }

        $item->resellersPivots = $resellers;
        $item->save();

        // Customers
        $customers = [];
        $ids       = array_filter(array_unique(
            array_merge($locationCustomers, array_keys($locationAssetsByCustomer)),
            SORT_REGULAR,
        ));

        foreach ($ids as $id) {
            $customers[$id]               = new LocationCustomer();
            $customers[$id]->assets_count = $locationAssetsByCustomer[$id] ?? 0;
        }

        $item->customersPivots = $customers;
        $item->save();

        // Countable
        $item->customers_count = count($customers);
        $item->assets_count    = $locationAssetsByLocation;
        $item->save();
    }
}
