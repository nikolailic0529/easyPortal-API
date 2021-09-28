<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Location;
use App\Utils\ModelHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function array_map;
use function array_sum;
use function count;

class LocationsRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-data-loader-locations-recalculate';
    }

    public function __invoke(): void {
        $keys      = $this->getKeys();
        $model     = new Location();
        $locations = Location::query()
            ->whereIn($model->getKeyName(), $this->getKeys())
            ->get();
        $assets    = $this->calculateAssets($keys, $locations);

        foreach ($locations as $location) {
            // Prepare
            $locationAssets = $assets[$location->getKey()] ?? [];

            // Countable
            $location->customers_count = count($locationAssets['customers']);
            $location->assets_count    = array_sum(Arr::flatten($locationAssets['customers']));

            $location->save();

            // Resellers
            $location->resellers()->sync(array_map(static function (array $resellers): array {
                return [
                    'customers_count' => count($resellers),
                    'assets_count'    => array_sum($resellers),
                ];
            }, $locationAssets['resellers'] ?? []));

            // Customers
            $location->customers()->sync(array_map(static function (array $customers): array {
                return [
                    'assets_count' => array_sum($customers),
                ];
            }, $locationAssets['customers'] ?? []));
        }
    }

    /**
     * Returns the number of assets on each customer on each location for each reseller.
     *
     * @param array<string>                                        $keys
     * @param \Illuminate\Support\Collection<\App\Models\Location> $locations
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateAssets(array $keys, Collection $locations): array {
        $assets = [];
        $result = (new ModelHelper(Location::query()))
            ->getRelation('assets')
            ->toBase()
            ->select('reseller_id', 'customer_id', 'location_id', DB::raw('count(*) as count'))
            ->whereIn('location_id', $keys)
            ->groupBy('reseller_id', 'customer_id', 'location_id')
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $l = $row->location_id;
            $r = (string) $row->reseller_id;
            $c = (string) $row->customer_id;

            $assets[$l]['resellers'][$r][$c] = (int) $row->count;
            $assets[$l]['customers'][$c][$r] = (int) $row->count;
        }

        return $assets;
    }
}
