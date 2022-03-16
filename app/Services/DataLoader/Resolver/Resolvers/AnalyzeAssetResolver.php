<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Logs\AnalyzeAsset;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Resolver<AnalyzeAsset>
 */
class AnalyzeAssetResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?AnalyzeAsset {
        return $this->resolve($id, $factory);
    }

    /**
     * @param array<string|int> $keys
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }

    protected function getFindQuery(): ?Builder {
        return AnalyzeAsset::query();
    }
}
