<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\CustomerLocation;
use App\Models\Data\Location;
use App\Models\ResellerLocation;
use App\Services\Recalculator\Processor\ChunkData;
use stdClass;

/**
 * @extends ChunkData<Location>
 */
class LocationsChunkData extends ChunkData {
    /**
     * @var array<string, array<string>>
     */
    private array $resellers;

    /**
     * @var array<string, array<string>>
     */
    private array $customers;

    public function getLocationAssetsCount(Location $location): int {
        return $this->getAssetsCount('location_id')[$location->getKey()] ?? 0;
    }

    /**
     * @return array<string>
     */
    public function getLocationResellers(Location $location): array {
        if (!isset($this->resellers)) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = ResellerLocation::query()
                ->select(['location_id', 'reseller_id'])
                ->whereIn('location_id', $keys)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var stdClass $row */
                $locationId          = (string) $row->location_id;
                $resellerId          = (string) $row->reseller_id;
                $data[$locationId][] = $resellerId;
            }

            // Save
            $this->resellers = $data;
        }

        return $this->resellers[$location->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getLocationAssetsByReseller(Location $location): array {
        return $this->getAssetsCountFor('location_id', 'reseller_id', 'reseller')[$location->getKey()] ?? [];
    }

    /**
     * @return array<string>
     */
    public function getLocationCustomers(Location $location): array {
        if (!isset($this->customers)) {
            // Calculate
            $data   = [];
            $keys   = $this->getKeys();
            $result = CustomerLocation::query()
                ->select(['location_id', 'customer_id'])
                ->whereIn('location_id', $keys)
                ->toBase()
                ->get();

            foreach ($result as $row) {
                /** @var stdClass $row */
                $locationId          = (string) $row->location_id;
                $customerId          = (string) $row->customer_id;
                $data[$locationId][] = $customerId;
            }

            // Save
            $this->customers = $data;
        }

        return $this->customers[$location->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getLocationAssetsByCustomer(Location $location): array {
        return $this->getAssetsCountFor('location_id', 'customer_id', 'customer')[$location->getKey()] ?? [];
    }
}
