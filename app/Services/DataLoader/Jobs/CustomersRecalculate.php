<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Customer;
use App\Utils\Eloquent\Model;

use function count;

/**
 * @extends \App\Services\DataLoader\Jobs\Recalculate<\App\Models\Customer>
 */
class CustomersRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-data-loader-customers-recalculate';
    }

    public function getModel(): Model {
        return new Customer();
    }

    protected function process(): void {
        $keys             = $this->getKeys();
        $model            = $this->getModel();
        $customers        = $model::query()
            ->whereIn($model->getKeyName(), $this->getKeys())
            ->with(['locations', 'contacts', 'statuses'])
            ->get();
        $assetsByCustomer = $this->calculateAssetsBy('customer_id', $keys);
        $assetsByLocation = $this->calculateAssetsByLocation('customer_id', $keys);

        foreach ($customers as $customer) {
            /** @var \App\Models\Customer $customer */
            $customer->locations_count = count($customer->locations);
            $customer->contacts_count  = count($customer->contacts);
            $customer->statuses_count  = count($customer->statuses);
            $customer->assets_count    = $assetsByCustomer[$customer->getKey()] ?? 0;

            foreach ($customer->locations as $location) {
                /** @var \App\Models\LocationCustomer $location */
                $location->assets_count = $assetsByLocation[$customer->getKey()][$location->location_id] ?? 0;
                $location->save();
            }

            $customer->save();
        }
    }
}
