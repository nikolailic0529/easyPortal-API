<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Reseller;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function array_fill_keys;
use function array_keys;
use function array_map;
use function array_merge;
use function array_sum;
use function count;

/**
 * @extends \App\Services\DataLoader\Jobs\Recalculate<\App\Models\Reseller>
 */
class ResellersRecalculate extends Recalculate {
    public function displayName(): string {
        return 'ep-data-loader-resellers-recalculate';
    }

    public function getModel(): Model {
        return new Reseller();
    }

    protected function process(): void {
        $keys      = $this->getKeys();
        $model     = $this->getModel();
        $resellers = $model::query()
            ->whereIn($model->getKeyName(), $this->getKeys())
            ->with(['locations', 'contacts'])
            ->get();
        $assets    = $this->calculateAssets($keys, $resellers);
        $documents = $this->calculateDocuments($keys, $resellers);

        foreach ($resellers as $reseller) {
            /** @var \App\Models\Reseller $reseller */
            // Prepare
            $resellerAssets = $assets[$reseller->getKey()] ?? [];

            // Countable
            $reseller->locations_count = count($reseller->locations);
            $reseller->customers_count = count($resellerAssets['customers'] ?? []);
            $reseller->contacts_count  = count($reseller->contacts);
            $reseller->assets_count    = array_sum(Arr::flatten($resellerAssets['customers'] ?? []));

            $reseller->save();

            // Locations
            foreach ($reseller->locations as $location) {
                /** @var \App\Models\LocationReseller $location */
                $location->customers_count = count($resellerAssets['locations'][$location->location_id] ?? []);
                $location->assets_count    = array_sum($resellerAssets['locations'][$location->location_id] ?? []);
                $location->save();
            }

            // Customers
            $customers = array_merge(
                array_fill_keys(array_keys($documents[$reseller->getKey()] ?? []), [
                    'locations_count' => 0,
                    'assets_count'    => 0,
                ]),
                array_map(static function (array $customers): array {
                    return [
                        'locations_count' => count($customers),
                        'assets_count'    => array_sum($customers),
                    ];
                }, $resellerAssets['customers'] ?? []),
            );

            unset($customers['']);

            $reseller->customers()->sync($customers);
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
        $result = Asset::query()
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

            $assets[$r]['locations'][$l][$c] = (int) $row->count + ($assets[$r]['locations'][$l][$c] ?? 0);
            $assets[$r]['customers'][$c][$l] = (int) $row->count + ($assets[$r]['customers'][$c][$l] ?? 0);
        }

        return $assets;
    }

    /**
     * Returns the number of documents on each customer for each reseller.
     *
     * @param array<string>                                        $keys
     * @param \Illuminate\Support\Collection<\App\Models\Reseller> $resellers
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateDocuments(array $keys, Collection $resellers): array {
        $assets = [];
        $result = Document::query()
            ->toBase()
            ->select('reseller_id', 'customer_id', DB::raw('count(*) as count'))
            ->whereIn('reseller_id', $keys)
            ->groupBy('reseller_id', 'customer_id')
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $r = $row->reseller_id;
            $c = (string) $row->customer_id;

            $assets[$r][$c] = (int) $row->count + ($assets[$r][$c] ?? 0);
        }

        return $assets;
    }
}
