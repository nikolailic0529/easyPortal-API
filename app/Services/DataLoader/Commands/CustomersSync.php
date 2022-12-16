<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\CustomersSynchronizer;

/**
 * @extends ObjectsSync<CustomersSynchronizer>
 */
class CustomersSync extends ObjectsSync {
    public function __invoke(CustomersSynchronizer $importer): int {
        return $this->process($importer);
    }
}
