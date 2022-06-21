<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Reseller;
use App\Services\Recalculator\Processor\ChunkData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * @extends ChunkData<Reseller>
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

    public function getResellerAssetsCount(Reseller $reseller): int {
        return $this->getAssetsCount('reseller_id')[$reseller->getKey()] ?? 0;
    }

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
                /** @var stdClass $row */
                $resellerId                     = (string) $row->reseller_id;
                $locationId                     = (string) $row->location_id;
                $data[$resellerId][$locationId] = (int) $row->count + ($data[$resellerId][$locationId] ?? 0);
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
                /** @var stdClass $row */
                $resellerId                     = (string) $row->reseller_id;
                $customerId                     = (string) $row->customer_id;
                $data[$resellerId][$customerId] = (int) $row->count + ($data[$resellerId][$customerId] ?? 0);
            }

            // Save
            $this->documentsByCustomer = $data;
        }

        return $this->documentsByCustomer[$reseller->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getResellerQuotesByCustomer(Reseller $reseller): array {
        return $this->getQuotesCountFor('reseller_id', 'customer_id', 'customer')[$reseller->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getResellerContractsByCustomer(Reseller $reseller): array {
        return $this->getContractsCountFor('reseller_id', 'customer_id', 'customer')[$reseller->getKey()] ?? [];
    }
}
