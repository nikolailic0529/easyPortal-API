<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Asset;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Resolver<Asset>
 */
class AssetResolver extends Resolver {
    /**
     * @param Closure(): Asset|null $factory
     *
     * @return ($factory is null ? Asset|null : Asset)
     */
    public function get(string|int $id, Closure $factory = null): ?Asset {
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Asset::withTrashed();
    }
}
