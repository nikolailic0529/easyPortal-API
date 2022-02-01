<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Reseller;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class ResellerResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?Reseller {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
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
