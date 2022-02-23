<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Asset;

use App\Models\Asset;
use App\Services\DataLoader\Jobs\AssetSync;
use Illuminate\Contracts\Container\Container;

class Sync {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    public function __invoke(Asset $asset): bool {
        $job    = $this->container->make(AssetSync::class)->init($asset);
        $result = $this->container->call($job);

        return $result;
    }
}
