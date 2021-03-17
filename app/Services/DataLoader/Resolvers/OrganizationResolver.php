<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolvers;

use App\Models\Organization;
use App\Services\DataLoader\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResolver extends Resolver {
    public function get(string|int $id, Closure $factory = null): ?Organization {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Organization::query();
    }

    /**
     * @param array<string|int> $keys
     */
    public function prefetch(array $keys, bool $reset = false): static {
        return parent::prefetch($keys, $reset);
    }
}
