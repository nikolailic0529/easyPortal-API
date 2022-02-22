<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor\Processors;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Services\Recalculator\Processor\ChunkData;

/**
 * @extends \App\Services\Recalculator\Processor\ChunkData<\App\Models\Customer>
 */
class CustomersChunkData extends ChunkData {
    public function getCustomerAssetsCount(Customer $customer): int {
        return $this->getAssetsCount('customer_id')[$customer->getKey()] ?? 0;
    }

    public function getCustomerAssetsCountFor(Customer $customer, CustomerLocation $location): int {
        $assets = $this->getAssetsCountFor('customer_id', 'location_id', 'location');
        $count  = $assets[$customer->getKey()][$location->location_id] ?? 0;

        return $count;
    }
}