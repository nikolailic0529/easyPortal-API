<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Location;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Services\Recalculator\Processor\ChunkData;

/**
 * @extends \App\Services\Recalculator\Processor\ChunkData<\App\Models\Location>
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

    /**
     * @return array<string,array<string>>
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
                /** @var \stdClass $row */
                $l = (string) $row->location_id;
                $r = (string) $row->reseller_id;

                $data[$l][] = $r;
            }

            // Save
            $this->resellers = $data;
        }

        return $this->resellers[$location->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getLocationResellersAssets(Location $location): array {
        return $this->getAssetsCountFor('location_id', 'reseller_id', 'reseller')[$location->getKey()] ?? [];
    }

    /**
     * @return array<string,array<string>>
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
                /** @var \stdClass $row */
                $l = (string) $row->location_id;
                $c = (string) $row->customer_id;

                $data[$l][] = $c;
            }

            // Save
            $this->customers = $data;
        }

        return $this->customers[$location->getKey()] ?? [];
    }

    /**
     * @return array<string, int>
     */
    public function getLocationCustomersAssets(Location $location): array {
        return $this->getAssetsCountFor('location_id', 'customer_id', 'customer')[$location->getKey()] ?? [];
    }
}
