<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Providers;

use App\Models\Asset;
use App\Services\DataLoader\Provider;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @internal
 */
class AssetProvider extends Provider {
    public function get(string|int $id, Closure $factory): Asset {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(mixed $key): ?Builder {
        return Asset::whereKey($key);
    }
}
