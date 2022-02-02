<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver;

use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Cache\KeyRetriever;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Singleton;
use App\Services\DataLoader\Exceptions\FactorySearchModeException;
use App\Services\DataLoader\Normalizer\Normalizer;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LogicException;

use function array_map;
use function is_array;
use function is_string;

/**
 * The provider performs a search of the model with given properties in the
 * database and returns it or call the factory if it does not exist. It also
 * implements advanced cache practices to reduce the number of database lookups.
 *
 * Important notes:
 * - providers must be independent of each other.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @internal
 */
abstract class Resolver implements Singleton, KeyRetriever {
    protected Cache|null $cache = null;

    public function __construct(
        protected Normalizer $normalizer,
        protected Collector $collector,
    ) {
        // empty
    }

    /**
     * @return \Illuminate\Support\Collection<TModel>
     */
    public function getResolved(): Collection {
        return $this->getCache()->getAll();
    }

    public function reset(): static {
        $this->getCache(false)->reset();

        return $this;
    }

    /**
     * @return TModel|null
     */
    protected function resolve(mixed $key, Closure $factory = null, bool $find = true): ?Model {
        // Model already in cache or can be found?
        $key   = $this->getCacheKey($key);
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
                $this->putNull($key);

                throw $exception;
            }
        }

        // Put into cache
        if ($model) {
            $this->put($model);
        } else {
            $this->putNull($key);
        }

        // Return
        return $model;
    }

    /**
     * @return TModel|null
     */
    protected function find(Key $key): ?Model {
        return $this->getFindQuery()?->where(function (Builder $builder) use ($key): Builder {
            return $this->getFindWhere($builder, $key);
        })->first();
    }

    /**
     * @param array<mixed> $keys
     * @param \Closure(\Illuminate\Database\Eloquent\Collection<TModel>):void|null $callback
     */
    protected function prefetch(array $keys, Closure|null $callback = null): static {
        // Possible?
        $builder = $this->getFindQuery();

        if (!$builder) {
            throw new LogicException('Prefetch cannot be used with Resolver without the find query.');
        }

        // Empty?
        if (!$keys) {
            return $this;
        }

        // Prefetch
        $keys  = array_map(fn(mixed $key): Key => $this->getCacheKey($key), $keys);
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
        $this->putNull($keys)->put($items);

        // Return
        return $this;
    }

    /**
     * @param TModel|\Illuminate\Support\Collection<TModel>|array<TModel> $object
     */
    protected function put(Model|Collection|array $object): void {
        $cache = $this->getCache();

        if ($object instanceof Collection || is_array($object)) {
            foreach ($object as $model) {
                if ($model !== null) {
                    $this->put($model);
                }
            }
        } else {
            $this->collector->collect($object);
            $cache->put($object);
        }
    }

    /**
     * @param \App\Services\DataLoader\Cache\Key|array<\App\Services\DataLoader\Cache\Key> $keys
     */
    protected function putNull(Key|array $keys): static {
        $cache = $this->getCache();

        if (is_array($keys)) {
            $cache->putNulls($keys);
        } else {
            $cache->putNull($keys);
        }

        return $this;
    }

    protected function getCache(bool $preload = true): Cache {
        if (!$this->cache) {
            $this->cache = new Cache($this->getKeyRetrievers());

            if ($preload) {
                $this->put($this->getPreloadedItems());
            }
        }

        return $this->cache;
    }

    protected function getCacheKey(mixed $key): Key {
        return new Key($this->normalizer, is_array($key) ? $key : [$key]);
    }

    /**
     * @return array<\App\Services\DataLoader\Cache\KeyRetriever>
     */
    protected function getKeyRetrievers(): array {
        return [
            'default' => $this,
        ];
    }

    /**
     * @param TModel $model
     */
    public function getKey(Model $model): Key {
        return $this->getCacheKey([
            $model->getKeyName() => $model->getKey(),
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<TModel>|null
     */
    protected function getFindQuery(): ?Builder {
        return null;
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Builder<TModel>
     *
     * @param T $builder
     *
     * @return T
     */
    protected function getFindWhere(Builder $builder, Key $key): Builder {
        foreach ($key->get() as $property => $value) {
            $builder = is_string($property)
                ? $this->getFindWhereProperty($builder, $property, $value)
                : $builder->whereKey($value);
        }

        return $builder;
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Builder<TModel>
     *
     * @param T $builder
     *
     * @return T
     */
    protected function getFindWhereProperty(Builder $builder, string $property, ?string $value): Builder {
        return $builder->where($property, '=', $value);
    }

    /**
     * @return \Illuminate\Support\Collection<TModel>
     */
    protected function getPreloadedItems(): Collection {
        return new Collection();
    }
}
