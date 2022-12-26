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
    /**
     * @param Closure(): AnalyzeAsset|null $factory
     *
     * @return ($factory is null ? AnalyzeAsset|null : AnalyzeAsset)
     */
    public function get(string|int $id, Closure $factory = null): ?AnalyzeAsset {
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return AnalyzeAsset::query();
    }
}
