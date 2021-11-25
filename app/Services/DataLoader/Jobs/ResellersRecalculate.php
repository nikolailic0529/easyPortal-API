<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\CustomerLocation;
use App\Models\Document;
use App\Models\Reseller;
use App\Utils\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use function array_fill_keys;
use function array_filter;
use function array_intersect;
use function array_keys;
use function array_merge;
use function array_reduce;
use function array_unique;
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
        $keys                = $this->getKeys();
        $model               = $this->getModel();
        $resellers           = $model::query()
            ->whereIn($model->getKeyName(), $this->getKeys())
            ->with(['locations', 'contacts', 'statuses'])
            ->get();
        $dataByLocation      = $this->calculateDataByLocation($keys, $resellers);
        $assetsByReseller    = $this->calculateAssetsByReseller($keys, $resellers);
        $assetsByCustomer    = $this->calculateAssetsByCustomer($keys, $resellers);
        $documentsByCustomer = $this->calculateDocumentsByCustomer($keys, $resellers);
        $customerLocations   = $this->calculateCustomerLocations(
            array_filter(array_keys(array_reduce(
                $assetsByCustomer,
                static function (array $customers, array $data): array {
                    return array_merge($customers, $data);
                },
                [],
            ))),
        );

        foreach ($resellers as $reseller) {
            /** @var \App\Models\Reseller $reseller */
            // Prepare
            $resellerAssetsByReseller    = $assetsByReseller[$reseller->getKey()] ?? 0;
            $resellerAssetsByCustomer    = $assetsByCustomer[$reseller->getKey()] ?? [];
            $resellerAssetsByLocation    = $dataByLocation['assets'][$reseller->getKey()] ?? [];
            $resellerCustomersByLocation = $dataByLocation['customers'][$reseller->getKey()] ?? [];
            $resellerDocumentsByCustomer = $documentsByCustomer[$reseller->getKey()] ?? [];
            $resellerCustomers           = array_filter(array_unique(array_merge(
                array_keys($resellerAssetsByCustomer),
                array_keys($resellerDocumentsByCustomer),
            )));

            // Countable
            $reseller->locations_count = count($reseller->locations);
            $reseller->customers_count = count($resellerCustomers);
            $reseller->contacts_count  = count($reseller->contacts);
            $reseller->statuses_count  = count($reseller->statuses);
            $reseller->assets_count    = $resellerAssetsByReseller;

            $reseller->save();

            // Locations
            foreach ($reseller->locations as $location) {
                /** @var \App\Models\LocationReseller $location */
                $location->customers_count = $resellerCustomersByLocation[$location->location_id] ?? 0;
                $location->assets_count    = $resellerAssetsByLocation[$location->location_id] ?? 0;
                $location->save();
            }

            // Customers
            $locations = array_filter(array_keys($resellerAssetsByLocation));
            $customers = array_fill_keys($resellerCustomers, [
                'locations_count' => 0,
                'assets_count'    => 0,
            ]);

            foreach ($resellerAssetsByCustomer as $customer => $assets) {
                if (isset($customers[$customer])) {
                    $customers[$customer]['assets_count']    = $assets;
                    $customers[$customer]['locations_count'] = count(array_intersect(
                        $customerLocations[$customer] ?? [],
                        $locations,
                    ));
                }
            }

            $reseller->customers()->sync($customers);
        }
    }

    /**
     * @param array<string>                                        $keys
     * @param \Illuminate\Support\Collection<\App\Models\Reseller> $resellers
     *
     * @return array<string,int>
     */
    protected function calculateAssetsByReseller(array $keys, Collection $resellers): array {
        $data   = [];
        $result = Asset::query()
            ->toBase()
            ->select('reseller_id', DB::raw('count(*) as count'))
            ->whereIn('reseller_id', $keys)
            ->groupBy('reseller_id')
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $r = $row->reseller_id;

            $data[$r] = (int) $row->count;
        }

        return $data;
    }

    /**
     * @param array<string>                                        $keys
     * @param \Illuminate\Support\Collection<\App\Models\Reseller> $resellers
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateAssetsByCustomer(array $keys, Collection $resellers): array {
        $data   = [];
        $result = Asset::query()
            ->toBase()
            ->select('reseller_id', 'customer_id', DB::raw('count(*) as count'))
            ->whereIn('reseller_id', $keys)
            ->groupBy('reseller_id', 'customer_id')
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $r = $row->reseller_id;
            $c = (string) $row->customer_id;

            $data[$r][$c] = (int) $row->count + ($data[$r][$c] ?? 0);
        }

        return $data;
    }

    /**
     * @param array<string>                                        $keys
     * @param \Illuminate\Support\Collection<\App\Models\Reseller> $resellers
     *
     * @return array{assets:array<string,array<string, int>>,customers:array<string,array<string, int>>}
     */
    protected function calculateDataByLocation(array $keys, Collection $resellers): array {
        $data   = [];
        $result = Asset::query()
            ->toBase()
            ->select(
                'reseller_id',
                'location_id',
                DB::raw('count(*) as assets_count'),
                DB::raw('count(DISTINCT `customer_id`) as customers_count'),
            )
            ->whereIn('reseller_id', $keys)
            ->groupBy('reseller_id', 'location_id')
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $r = $row->reseller_id;
            $l = (string) $row->location_id;

            $data['assets'][$r][$l]    = (int) $row->assets_count + ($data['assets'][$r][$l] ?? 0);
            $data['customers'][$r][$l] = (int) $row->customers_count + ($data['customers'][$r][$l] ?? 0);
        }

        return $data;
    }

    /**
     * @param array<string>                                        $keys
     * @param \Illuminate\Support\Collection<\App\Models\Reseller> $resellers
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateDocumentsByCustomer(array $keys, Collection $resellers): array {
        $documents = [];
        $result    = Document::query()
            ->toBase()
            ->select('reseller_id', 'customer_id', DB::raw('count(*) as count'))
            ->whereIn('reseller_id', $keys)
            ->groupBy('reseller_id', 'customer_id')
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $r = $row->reseller_id;
            $c = (string) $row->customer_id;

            $documents[$r][$c] = (int) $row->count + ($documents[$r][$c] ?? 0);
        }

        return $documents;
    }

    /**
     * @param array<string> $customers
     *
     * @return array<string,string>
     */
    protected function calculateCustomerLocations(array $customers): array {
        $data   = [];
        $result = CustomerLocation::query()
            ->select(['customer_id', 'location_id'])
            ->whereIn('customer_id', $customers)
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $c = (string) $row->customer_id;
            $l = (string) $row->location_id;

            $data[$c][] = $l;
        }

        return $data;
    }
}
