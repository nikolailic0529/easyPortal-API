<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Distributor;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Distributor>
 */
class DistributorResolver extends Resolver {
    /**
     * @param Closure(): Distributor|null $factory
     *
     * @return ($factory is null ? Distributor|null : Distributor)
     */
    public function get(string|int $id, Closure $factory = null): ?Distributor {
        return $this->resolve($id, $factory);
    }

    protected function getPreloadedItems(): Collection {
        return Distributor::withTrashed()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Distributor::withTrashed();
    }
}
