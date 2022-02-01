<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Customer;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends \App\Services\DataLoader\Resolver<\App\Models\Customer>
 */
class CustomerResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?Customer {
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Customer::query();
    }

    /**
     * @param array<string|int> $keys
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }
}
