<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\DistributorsSynchronizer;

/**
 * @extends ObjectsSync<DistributorsSynchronizer>
 */
class DistributorsSync extends ObjectsSync {
    public function __invoke(DistributorsSynchronizer $importer): int {
        return $this->process($importer);
    }
}
