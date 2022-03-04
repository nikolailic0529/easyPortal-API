<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Services\Recalculator\Processor\Processor;
use App\Utils\Processor\State;
use Illuminate\Database\Eloquent\Collection;

use function array_filter;
use function array_intersect;
use function array_keys;
use function array_merge;
use function array_unique;
use function count;

use const SORT_REGULAR;

/**
 * @extends \App\Services\Recalculator\Processor\Processor<\App\Models\Reseller,\App\Services\Recalculator\Processor\Processors\ResellersChunkData>
 */
class ResellersProcessor extends Processor {
    protected function getModel(): string {
        return Reseller::class;
    }

    /**
     * @inheritDoc
     */
    protected function prefetch(State $state, array $items): mixed {
        $items = (new Collection($items))->load(['locations', 'contacts', 'statuses', 'customersPivots']);
        $data  = new ResellersChunkData($items);

        return $data;
    }

    /**
     * @param \App\Utils\Processor\EloquentState<\App\Models\Reseller>           $state
     * @param \App\Services\Recalculator\Processor\Processors\ResellersChunkData $data
     * @param \App\Models\Reseller                                               $item
     */
    protected function process(State $state, mixed $data, mixed $item): void {
        // Prepare
        $reseller                    = $item;
        $resellerAssets              = $data->getResellerAssetsCount($reseller);
        $resellerAssetsByCustomer    = $data->getResellerAssetsByCustomer($reseller);
        $resellerAssetsByLocation    = $data->getResellerAssetsByLocation($reseller);
        $resellerCustomersByLocation = $data->getResellerCustomersByLocation($reseller);
        $resellerDocumentsByCustomer = $data->getResellerDocumentsByCustomer($reseller);
        $resellerCustomerLocations   = $data->getResellerCustomerLocations($reseller);
        $resellerCustomers           = array_filter(array_unique(array_merge(
            array_keys($resellerAssetsByCustomer),
            array_keys($resellerDocumentsByCustomer),
        )));

        // Countable
        $reseller->locations_count = count($reseller->locations);
        $reseller->customers_count = count($resellerCustomers);
        $reseller->contacts_count  = count($reseller->contacts);
        $reseller->statuses_count  = count($reseller->statuses);
        $reseller->assets_count    = $resellerAssets;

        $reseller->save();

        // Locations
        foreach ($reseller->locations as $location) {
            /** @var \App\Models\ResellerLocation $location */
            $location->customers_count = $resellerCustomersByLocation[$location->location_id] ?? 0;
            $location->assets_count    = $resellerAssetsByLocation[$location->location_id] ?? 0;
            $location->save();
        }

        // Customers
        $locations = array_filter(array_keys($resellerAssetsByLocation));
        $customers = [];
        $existing  = $reseller->customersPivots->keyBy(
            $reseller->customers()->getRelatedPivotKeyName(),
        );
        $ids       = array_filter(array_unique(
            array_merge($resellerCustomers, array_keys($resellerAssetsByCustomer)),
            SORT_REGULAR,
        ));

        foreach ($ids as $id) {
            $customers[$id]                  = new ResellerCustomer();
            $customers[$id]->assets_count    = $resellerAssetsByCustomer[$id] ?? 0;
            $customers[$id]->locations_count = count(array_intersect(
                $resellerCustomerLocations[$id] ?? [],
                $locations,
            ));

            unset($existing[$id]);
        }

        foreach ($existing as $id => $customer) {
            /** @var \App\Models\ResellerCustomer $customer */
            if ($customer->kpi_id !== null) {
                $customers[$id]                  = $customer;
                $customers[$id]->assets_count    = 0;
                $customers[$id]->locations_count = 0;
            }
        }

        $reseller->customersPivots = $customers;
        $reseller->save();
    }
}
