<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Customer;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class CustomerResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?Customer {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Customer::query();
    }

    /**
     * @param array<string|int> $keys
     */
    public function prefetch(array $keys, bool $reset = false, Closure|null $callback = null): static {
        return parent::prefetch($keys, $reset, $callback);
    }
}
