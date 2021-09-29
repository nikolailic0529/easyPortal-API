<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Customer;
use App\Models\Model;
use App\Utils\ModelHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function array_sum;
use function count;

class CustomersRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-data-loader-customers-recalculate';
    }

    public function getModel(): Model {
        return new Customer();
    }

    public function __invoke(): void {
        $keys      = $this->getKeys();
        $model     = $this->getModel();
        $customers = $model::query()
            ->whereIn($model->getKeyName(), $this->getKeys())
            ->with('locations')
            ->get();
        $assets    = $this->calculateAssets($keys, $customers);

        foreach ($customers as $customer) {
            $customer->locations_count = count($customer->locations);
            $customer->assets_count    = array_sum($assets[$customer->getKey()] ?? []);

            foreach ($customer->locations as $location) {
                /** @var \App\Models\LocationCustomer $location */
                $location->assets_count = $assets[$customer->getKey()][$location->location_id] ?? 0;
                $location->save();
            }

            $customer->save();
        }
    }

    /**
     * Returns the number of assets on each location for each customer.
     *
     * @param array<string>                                        $keys
     * @param \Illuminate\Support\Collection<\App\Models\Customer> $customers
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateAssets(array $keys, Collection $customers): array {
        $assets = [];
        $result = (new ModelHelper(Customer::query()))
            ->getRelation('assets')
            ->toBase()
            ->select('customer_id', 'location_id', DB::raw('count(*) as count'))
            ->whereIn('customer_id', $keys)
            ->groupBy('customer_id', 'location_id')
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $c = $row->customer_id;
            $l = (string) $row->location_id;

            $assets[$c][$l] = (int) $row->count + ($assets[$c][$l] ?? 0);
        }

        return $assets;
    }
}
