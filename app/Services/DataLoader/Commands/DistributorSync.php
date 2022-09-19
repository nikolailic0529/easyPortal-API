<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader\Loaders\DistributorLoader;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectSync<DistributorLoader>
 */
class DistributorSync extends ObjectSync {
    public function __invoke(Formatter $formatter, DistributorLoader $loader): int {
        return $this->process($formatter, $loader);
    }
}
