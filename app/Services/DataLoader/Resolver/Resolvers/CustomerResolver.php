<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Customer;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Resolver<Customer>
 */
class CustomerResolver extends Resolver {
    /**
     * @param Closure(\App\Services\DataLoader\Normalizer\Normalizer=): Customer|null $factory
     *
     * @return ($factory is null ? Customer|null : Customer)
     */
    public function get(string|int $id, Closure $factory = null): ?Customer {
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Customer::query();
    }

    /**
     * @inheritDoc
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }
}
