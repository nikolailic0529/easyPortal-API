<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use App\Services\DataLoader\Processors\Loader\Loaders\DistributorLoader;

/**
 * @extends ObjectSync<DistributorLoader>
 */
class DistributorSync extends ObjectSync {
    public function __invoke(DistributorLoader $loader): int {
        return $this->process($loader);
    }
}
