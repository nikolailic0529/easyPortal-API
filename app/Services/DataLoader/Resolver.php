<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\ModelKey;
use App\Services\DataLoader\Container\Singleton;
use App\Services\DataLoader\Exceptions\FactorySearchModeException;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;
use LogicException;

use function is_array;

/**
 * The provider performs a search of the model with given properties in the
 * database and returns it or call the factory if it does not exist. It also
 * implements advanced cache practices to reduce the number of database lookups.
 *
 * Important notes:
 * - providers must be independent of each other.
 *
 * @internal
 */
abstract class Resolver implements Singleton {
    protected Cache|null $cache = null;

    public function __construct(
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    /**
     * @return \Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model>
     */
    public function getResolved(): Collection {
        return $this->getCache()->getAll();
    }

    public function reset(): static {
        $this->getCache(false)->reset();

        return $this;
    }

    protected function resolve(mixed $key, Closure $factory = null, bool $find = true): ?Model {
        // Model already in cache or can be found?
        $key   = $this->normalizer->key($key);
        $cache = $this->getCache();
        $model = null;

        if ($cache->has($key)) {
            $model = $cache->get($key);
        } elseif ($find) {
            $model = $this->find($key);
        } else {
            // empty
        }

        // Not found? Well, maybe we can create?
        if (!$model && $factory) {
            try {
                $model = $factory($this->normalizer);
            } catch (FactorySearchModeException $exception) {
                $cache->putNull($key);

                throw $exception;
            }
        }

        // Put into cache
        if ($model) {
            $cache->put($model);
        } else {
            $cache->putNull($key);
        }

        // Return
        return $model;
    }

    protected function find(mixed $key): ?Model {
        return $this->getFindQuery()?->where(function (Builder $builder) use ($key): Builder {
            return $this->getFindWhere($builder, $key);
        })->first();
    }

    /**
     * @param array<mixed> $keys
     * @param \Closure(\Illuminate\Database\Eloquent\Collection):void|null $callback
     */
    protected function prefetch(array $keys, bool $reset = false, Closure|null $callback = null): static {
        // Possible?
        $builder = $this->getFindQuery();

        if (!$builder) {
            throw new LogicException('Prefetch cannot be used with Resolver without the find query.');
        }

        // Reset?
        if ($reset) {
            $this->reset();
        }

        // Empty?
        if (!$keys) {
            return $this;
        }

        // Prefetch
        $items = $builder
            ->where(function (Builder $builder) use ($keys): Builder {
                foreach ($keys as $key) {
                    $builder = $builder->orWhere(function (Builder $builder) use ($key): Builder {
                        return $this->getFindWhere($builder, $key);
                    });
                }

                return $builder;
            })
            ->get();

        if ($callback) {
            $callback($items);
        }

        // Fill cache
        $this->getCache()->putNulls($keys)->putAll($items);

        // Return
        return $this;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model
     *      |\Illuminate\Support\Collection<\Illuminate\Database\Eloquent\Model>
     *      |array<\Illuminate\Database\Eloquent\Model> $object
     */
    protected function put(Model|Collection|array $object): void {
        $cache = $this->getCache();

        if ($object instanceof Collection || is_array($object)) {
            foreach ($object as $model) {
                if ($model !== null) {
                    $cache->put($model);
                }
            }
        } else {
            $cache->put($object);
        }
    }

    protected function getCache(bool $preload = true): Cache {
        if (!$this->cache) {
            $items       = $preload ? $this->getPreloadedItems() : new Collection();
            $this->cache = new Cache($items, $this->getKeyRetrievers());
        }

        return $this->cache;
    }

    /**
     * @return array<\App\Services\DataLoader\Cache\KeyRetriever>
     */
    #[Pure]
    protected function getKeyRetrievers(): array {
        return [
            '_' => new ModelKey(),
        ];
    }

    #[Pure]
    protected function getFindQuery(): ?Builder {
        return null;
    }

    #[Pure]
    protected function getFindWhere(Builder $builder, mixed $key): Builder {
        if (is_array($key)) {
            foreach ($key as $property => $value) {
                $builder->where($property, '=', $value);
            }
        } else {
            $builder->whereKey($key);
        }

        return $builder;
    }

    #[Pure]
    protected function getPreloadedItems(): Collection {
        return new Collection();
    }
}
