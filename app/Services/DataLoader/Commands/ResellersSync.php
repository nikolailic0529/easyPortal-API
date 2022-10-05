<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Synchronizer\Synchronizers\ResellersSynchronizer;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsSync<ResellersSynchronizer>
 */
class ResellersSync extends ObjectsSync {
    public function __invoke(Formatter $formatter, ResellersSynchronizer $importer): int {
        return $this->process($formatter, $importer);
    }
}
