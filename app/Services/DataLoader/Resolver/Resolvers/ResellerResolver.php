<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Reseller;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Resolver<Reseller>
 */
class ResellerResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?Reseller {
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Reseller::query();
    }

    /**
     * @param array<string|int> $keys
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }
}
