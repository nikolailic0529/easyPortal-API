<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Reseller;
use App\Utils\ModelHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function array_map;
use function array_sum;
use function count;

class ResellersRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-data-loader-resellers-recalculate';
    }

    public function __invoke(): void {
        $keys      = $this->getKeys();
        $model     = new Reseller();
        $resellers = Reseller::query()
            ->whereIn($model->getKeyName(), $this->getKeys())
            ->with('locations')
            ->get();
        $assets    = $this->calculateAssets($keys, $resellers);

        foreach ($resellers as $reseller) {
            // Prepare
            $resellerAssets = $assets[$reseller->getKey()] ?? [];

            // Countable
            $reseller->locations_count = count($reseller->locations);
            $reseller->customers_count = count($resellerAssets['customers']);
            $reseller->assets_count    = array_sum(Arr::flatten($resellerAssets['customers']));

            $reseller->save();

            // Locations
            foreach ($reseller->locations as $location) {
                /** @var \App\Models\LocationReseller $location */
                $location->customers_count = count($resellerAssets['locations'][$location->location_id] ?? []);
                $location->assets_count    = array_sum($resellerAssets['locations'][$location->location_id] ?? []);
                $location->save();
            }

            // Customers
            $reseller->customers()->sync(array_map(static function (array $customers): array {
                return [
                    'locations_count' => count($customers),
                    'assets_count'    => array_sum($customers),
                ];
            }, $resellerAssets['customers'] ?? []));
        }
    }

    /**
     * Returns the number of assets on each customer on each location for each reseller.
     *
     * @param array<string>                                        $keys
     * @param \Illuminate\Support\Collection<\App\Models\Reseller> $resellers
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateAssets(array $keys, Collection $resellers): array {
        $assets = [];
        $result = (new ModelHelper(Reseller::query()))
            ->getRelation('assets')
            ->toBase()
            ->select('reseller_id', 'customer_id', 'location_id', DB::raw('count(*) as count'))
            ->whereIn('reseller_id', $keys)
            ->groupBy('reseller_id', 'customer_id', 'location_id')
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $r = $row->reseller_id;
            $c = (string) $row->customer_id;
            $l = (string) $row->location_id;

            $assets[$r]['locations'][$l][$c] = (int) $row->count;
            $assets[$r]['customers'][$c][$l] = (int) $row->count;
        }

        return $assets;
    }
}
