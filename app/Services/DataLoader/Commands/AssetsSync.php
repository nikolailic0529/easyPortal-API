<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\AssetsSynchronizer;

/**
 * @extends ObjectsSync<AssetsSynchronizer>
 */
class AssetsSync extends ObjectsSync {
    public function __invoke(AssetsSynchronizer $importer): int {
        return $this->process($importer);
    }
}
