<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\AssetCoverage;
use App\Services\DataLoader\Cache\ClosureKey;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Support\Collection;

class AssetCoverageResolver extends Resolver {
    public function get(string $key, Closure $factory = null): ?AssetCoverage {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($key, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return AssetCoverage::query()->get();
    }

    /**
     * @inheritdoc
     */
    protected function getKeyRetrievers(): array {
        return [
            'key' => new ClosureKey(static function (AssetCoverage $coverage): string {
                return $coverage->key;
            }),
        ];
    }
}
