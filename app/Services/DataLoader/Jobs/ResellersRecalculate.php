<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Models\Asset;
use App\Models\CustomerLocation;
use App\Models\Document;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Utils\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

use function array_filter;
use function array_intersect;
use function array_keys;
use function array_merge;
use function array_reduce;
use function array_unique;
use function count;

use const SORT_REGULAR;

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
            ->with(['locations', 'contacts', 'statuses', 'customersPivots'])
            ->get();
        $assetsByReseller    = $this->calculateAssetsFor('reseller_id', $keys);
        $assetsByLocation    = $this->calculateAssetsByLocationFor('reseller_id', $keys);
        $assetsByCustomer    = $this->calculateAssetsByCustomerFor('reseller_id', $keys);
        $customersByLocation = $this->calculateCustomersByLocation($keys);
        $documentsByCustomer = $this->calculateDocumentsByCustomer($keys);
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
            $resellerAssetsByLocation    = $assetsByLocation[$reseller->getKey()] ?? [];
            $resellerCustomersByLocation = $customersByLocation[$reseller->getKey()] ?? [];
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
                    $customerLocations[$id] ?? [],
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

    /**
     * @param array<string> $keys
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateCustomersByLocation(array $keys): array {
        $data   = [];
        $result = Asset::query()
            ->select(['reseller_id', 'location_id', DB::raw('count(DISTINCT `customer_id`) as count')])
            ->whereIn('reseller_id', $keys)
            ->where(static function (Builder $builder): void {
                $builder
                    ->orWhereNull('customer_id')
                    ->orWhereHasIn('customer');
            })
            ->where(static function (Builder $builder): void {
                $builder
                    ->orWhereNull('location_id')
                    ->orWhereHasIn('location');
            })
            ->groupBy('reseller_id', 'location_id')
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $r = (string) $row->reseller_id;
            $l = (string) $row->location_id;

            $data[$r][$l] = (int) $row->count + ($data[$r][$l] ?? 0);
        }

        return $data;
    }

    /**
     * @param array<string> $keys
     *
     * @return array<string,array<string, int>>
     */
    protected function calculateDocumentsByCustomer(array $keys): array {
        $documents = [];
        $result    = Document::query()
            ->select(['reseller_id', 'customer_id', DB::raw('count(*) as count')])
            ->whereIn('reseller_id', $keys)
            ->where(static function (Builder $builder): void {
                $builder
                    ->orWhereNull('customer_id')
                    ->orWhereHasIn('customer');
            })
            ->groupBy('reseller_id', 'customer_id')
            ->toBase()
            ->get();

        foreach ($result as $row) {
            /** @var \stdClass $row */
            $r = (string) $row->reseller_id;
            $c = (string) $row->customer_id;

            $documents[$r][$c] = (int) $row->count + ($documents[$r][$c] ?? 0);
        }

        return $documents;
    }

    /**
     * @param array<string> $customers
     *
     * @return array<string,array<string>>
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
