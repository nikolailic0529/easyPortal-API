<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Synchronizer\Synchronizers\AssetsSynchronizer;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsSync<AssetsSynchronizer>
 */
class AssetsSync extends ObjectsSync {
    public function __invoke(Formatter $formatter, AssetsSynchronizer $importer): int {
        return $this->process($formatter, $importer);
    }
}
