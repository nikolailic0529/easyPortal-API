<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Loader\Loaders\DistributorLoader;
use App\Services\I18n\Formatter;

/**
 * @extends ObjectUpdate<DistributorLoader>
 */
class DistributorUpdate extends ObjectUpdate {
    public function __invoke(Formatter $formatter, DistributorLoader $loader): int {
        return $this->process($formatter, $loader);
    }
}
