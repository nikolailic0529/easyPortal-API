<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Reseller;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Resolver<Reseller>
 */
class ResellerResolver extends Resolver {
    /**
     * @param Closure(Normalizer=): Reseller|null $factory
     *
     * @return ($factory is null ? Reseller|null : Reseller)
     */
    public function get(string|int $id, Closure $factory = null): ?Reseller {
        return $this->resolve($id, $factory);
    }

    protected function getFindQuery(): ?Builder {
        return Reseller::withTrashed();
    }

    /**
     * @inheritDoc
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }
}
