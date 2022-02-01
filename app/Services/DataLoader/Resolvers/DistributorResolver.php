<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Distributor;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DistributorResolver extends Resolver implements SingletonPersistent {
    public function get(string|int $id, Closure $factory = null): ?Distributor {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($id, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Distributor::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Distributor::query();
    }

    /**
     * @param array<string|int> $keys
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }
}
