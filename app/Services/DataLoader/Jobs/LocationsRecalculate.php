<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\CustomerLocation;
use App\Models\Location;
use App\Models\ResellerLocation;
use App\Utils\Eloquent\Model;

use function array_fill_keys;
use function count;

/**
 * @extends \App\Services\DataLoader\Jobs\Recalculate<\App\Models\Location>
 */
class LocationsRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-data-loader-locations-recalculate';
    }

    public function getModel(): Model {
        return new Location();
    }

    protected function process(): void {
        $keys               = $this->getKeys();
        $model              = $this->getModel();
        $locations          = $model::query()
            ->whereIn($model->getKeyName(), $this->getKeys())
            ->get();
        $assetsByLocation   = $this->calculateAssetsFor('location_id', $keys);
        $assetsByReseller   = $this->calculateAssetsByResellerFor('location_id', $keys);
        $assetsByCustomer   = $this->calculateAssetsByCustomerFor('location_id', $keys);
        $locationsResellers = $this->calculateLocationResellers($keys);
        $locationsCustomers = $this->calculateLocationCustomers($keys);

        foreach ($locations as $location) {
            /** @var \App\Models\Location $location */
            // Prepare
            $locationResellers        = $locationsResellers[$location->getKey()] ?? [];
            $locationCustomers        = $locationsCustomers[$location->getKey()] ?? [];
            $locationAssetsByReseller = $assetsByReseller[$location->getKey()] ?? [];
            $locationAssetsByCustomer = $assetsByCustomer[$location->getKey()] ?? [];
            $locationAssetsByLocation = $assetsByLocation[$location->getKey()] ?? 0;

            // Resellers
            $resellers = array_fill_keys($locationResellers, [
                'assets_count' => 0,
            ]);

            foreach ($locationAssetsByReseller as $reseller => $assets) {
                if ($reseller) {
                    $resellers[$reseller]['assets_count'] = $assets;
                }
            }

            $location->resellersPivots = $resellers;
            $location->save();

            // Customers
            $customers = array_fill_keys($locationCustomers, [
                'assets_count' => 0,
            ]);

            foreach ($locationAssetsByCustomer as $customer => $assets) {
                if ($customer) {
                    $customers[$customer]['assets_count'] = $assets;
                }
            }

            $location->customersPivots = $customers;
            $location->save();

            // Countable
            $location->customers_count = count($customers);
            $location->assets_count    = $locationAssetsByLocation;
            $location->save();
        }
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string,array<string>>
     */
    protected function calculateLocationResellers(array $keys): array {
        $data   = [];
        $result = ResellerLocation::query()
            ->select(['location_id', 'reseller_id'])
            ->whereIn('location_id', $keys)
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $l = (string) $row->location_id;
            $r = (string) $row->reseller_id;

            $data[$l][] = $r;
        }

        return $data;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string,array<string>>
     */
    protected function calculateLocationCustomers(array $keys): array {
        $data   = [];
        $result = CustomerLocation::query()
            ->select(['location_id', 'customer_id'])
            ->whereIn('location_id', $keys)
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $l = (string) $row->location_id;
            $c = (string) $row->customer_id;

            $data[$l][] = $c;
        }

        return $data;
    }
}
