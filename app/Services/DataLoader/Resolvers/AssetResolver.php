<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Asset;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class AssetResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?Asset {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Asset::query();
    }
}
