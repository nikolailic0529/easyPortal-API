<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\ResellersSynchronizer;

/**
 * @extends ObjectsSync<ResellersSynchronizer>
 */
class ResellersSync extends ObjectsSync {
    public function __invoke(ResellersSynchronizer $importer): int {
        return $this->process($importer);
    }
}
