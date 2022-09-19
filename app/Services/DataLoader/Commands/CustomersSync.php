<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Synchronizer\Synchronizers\CustomersSynchronizer;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectsSync<CustomersSynchronizer>
 */
class CustomersSync extends ObjectsSync {
    public function __invoke(Formatter $formatter, CustomersSynchronizer $importer): int {
        return $this->process($formatter, $importer);
    }
}
