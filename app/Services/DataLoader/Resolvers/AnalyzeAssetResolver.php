<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Logs\AnalyzeAsset;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class AnalyzeAssetResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?AnalyzeAsset {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
