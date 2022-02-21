<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Asset;
use App\Models\CustomerLocation;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\Recalculator\Processor\ChunkData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

use function array_filter;
use function array_keys;
use function array_merge;
use function array_reduce;

/**
 * @extends \App\Services\Recalculator\Processor\ChunkData<\App\Models\Reseller>
 */
class ResellersChunkData extends ChunkData {
    /**
     * @var array<string, array<string, int>>
     */
    private array $customersByLocation;

    /**
     * @var array<string, array<string, int>>
     */
    private array $documentsByCustomer;

    /**
     * @var array<string,array<string>>
     */
    private array $customerLocations;

    /**
     * @return array<string, int>
     */
    public function getResellerAssetsByLocation(Reseller $reseller): array {
        return $this->getAssetsCountFor('reseller_id', 'location_id', 'location')[$reseller->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getResellerAssetsByCustomer(Reseller $reseller): array {
        return $this->getAssetsCountFor('reseller_id', 'customer_id', 'customer')[$reseller->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getResellerCustomersByLocation(Reseller $reseller): array {
        if (!isset($this->customersByLocation)) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
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

            // Save
            $this->customersByLocation = $data;
        }

        return $this->customersByLocation[$reseller->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getResellerDocumentsByCustomer(Reseller $reseller): array {
        if (!isset($this->documentsByCustomer)) {
            // Calculated
            $data   = [];
            $keys   = $this->getKeys();
            $result = Document::query()
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

                $data[$r][$c] = (int) $row->count + ($data[$r][$c] ?? 0);
            }

            // Save
            $this->documentsByCustomer = $data;
        }

        return $this->documentsByCustomer[$reseller->getKey()] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getResellerCustomerLocations(Reseller $reseller): array {
        if (!isset($this->customerLocations[$reseller->getKey()])) {
            // Calculate
            $data      = [];
            $assets    = $this->getResellerAssetsByCustomer($reseller);
            $customers = array_filter(array_keys($assets));
            $result    = CustomerLocation::query()
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

            // Save
            $this->customerLocations[$reseller->getKey()] = $data;
        }

        return $this->customerLocations[$reseller->getKey()] ?? [];
    }
}
