<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\DistributorsSynchronizer;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsSync<DistributorsSynchronizer>
 */
class DistributorsSync extends ObjectsSync {
    public function __invoke(Formatter $formatter, DistributorsSynchronizer $importer): int {
        return $this->process($formatter, $importer);
    }
}
