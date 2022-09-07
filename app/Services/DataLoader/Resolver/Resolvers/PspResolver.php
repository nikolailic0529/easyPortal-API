<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver\Resolvers;

use App\Models\Psp;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Container\SingletonPersistent;
use App\Services\DataLoader\Normalizer\Normalizer;
use App\Services\DataLoader\Resolver\Resolver;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @extends Resolver<Psp>
 */
class PspResolver extends Resolver implements SingletonPersistent {
    /**
     * @param Closure(Normalizer=): Psp|null $factory
     *
     * @return ($factory is null ? Psp|null : Psp)
     */
    public function get(string $key, Closure $factory = null): ?Psp {
        return $this->resolve($this->getUniqueKey($key), $factory);
    }

    /**
     * @inheritDoc
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
        return parent::prefetch($keys, $callback);
    }

    protected function getPreloadedItems(): Collection {
        return Psp::query()->get();
    }

    protected function getFindQuery(): ?Builder {
        return Psp::query();
    }

    public function getKey(Model $model): Key {
        return $this->getCacheKey($this->getUniqueKey($model->key));
    }

    /**
     * @return array{key: string}
     */
    protected function getUniqueKey(string $key): array {
        return [
            'key' => $key,
        ];
    }
}
