<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Resolver;

use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\Key;
use App\Services\DataLoader\Cache\KeyRetriever;
use App\Services\DataLoader\Collector\Collector;
use App\Services\DataLoader\Container\Singleton;
use App\Services\DataLoader\Container\SingletonPersistent;
use Closure;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use LogicException;

use function is_array;
use function is_string;

/**
 * The resolver performs a search of the model with given properties in the
 * database and returns it or call the factory if it does not exist. It also
 * implements advanced cache practices to reduce the number of database lookups.
 *
 * Important notes:
 * - resolvers must be independent of each other.
 *
 * @template TModel of Model
 *
 * @implements KeyRetriever<TModel>
 *
 * @internal
 */
abstract class Resolver implements Singleton, KeyRetriever {
    /**
     * @var Cache<TModel>|null
     */
    protected Cache|null $cache = null;

    public function __construct(
        protected Collector $collector,
    ) {
        // empty
    }

    /**
     * @return EloquentCollection<int, TModel>
     */
    public function getResolved(): EloquentCollection {
        return $this->getCache()->getAll();
    }

    public function reset(): static {
        $this->getCache()->reset();

        return $this;
    }

    /**
     * @param Closure(?TModel): TModel|null $factory
     *
     * @return ($factory is null ? TModel|null : TModel)
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

        // Factory?
        if ($factory) {
            try {
                $model = $factory($model);
            } catch (Exception $exception) {
                if ($model === null) {
                    $this->putNull($key);
                }

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
     * @param Collection<array-key, ?TModel>|array<?TModel>|TModel $models
     */
    public function add(Collection|array|Model $models): static {
        if ($models instanceof Model) {
            $models = [$models];
        }

        $cache = $this->getCache();

        foreach ($models as $model) {
            if ($model && $cache->get($this->getKey($model)) === null) {
                $this->put($model);
            }
        }

        return $this;
    }

    /**
     * @param array<mixed>                                              $keys
     * @param Closure(EloquentCollection<array-key, TModel>): void|null $callback
     */
    public function prefetch(array $keys, Closure|null $callback = null): static {
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
        $cache     = $this->getCache();
        $items     = new EloquentCollection();
        $internal  = [];
        $processed = [];

        foreach ($keys as $key) {
            if ($key === null || isset($processed[$key])) {
                continue;
            }

            $processed[$key] = true;
            $cacheKey        = $this->getCacheKey($key);
            $item            = $cache->get($cacheKey);

            if ($item === null && !$cache->hasNull($cacheKey)) {
                $internal[] = $cacheKey;
            } elseif ($item) {
                $items[] = $item;
            } else {
                // empty
            }
        }

        if ($internal) {
            $loaded = $builder
                ->where(function (Builder $builder) use ($internal): Builder {
                    foreach ($internal as $key) {
                        $builder = $builder->orWhere(function (Builder $builder) use ($key): Builder {
                            return $this->getFindWhere($builder, $key);
                        });
                    }

                    return $builder;
                })
                ->get();
            $items  = $items->merge($loaded);

            $this->putNull($internal)->put($loaded);
        }

        if ($callback) {
            $callback($items);
        }

        // Return
        return $this;
    }

    /**
     * @param TModel|Collection<array-key, ?TModel>|array<?TModel> $object
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
            if (!($this instanceof SingletonPersistent)) {
                $this->collector->collect($object);
            }

            $cache->put($object);
        }
    }

    /**
     * @param Key|array<Key> $keys
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

    /**
     * @return Cache<TModel>
     */
    protected function getCache(): Cache {
        if (!$this->cache) {
            /** @var Cache<TModel> $cache */
            $cache       = new Cache($this->getKeyRetrievers());
            $this->cache = $cache;

            $this->put($this->getPreloadedItems());
        }

        return $this->cache;
    }

    protected function getCacheKey(mixed $key): Key {
        return new Key(is_array($key) ? $key : [$key]);
    }

    /**
     * @return array<KeyRetriever<TModel>>
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
     * @return Builder<TModel>|null
     */
    protected function getFindQuery(): ?Builder {
        return null;
    }

    /**
     * @template T of Builder<TModel>
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
     * @template T of Builder<TModel>
     *
     * @param T $builder
     *
     * @return T
     */
    protected function getFindWhereProperty(Builder $builder, string $property, ?string $value): Builder {
        return $builder->where($property, '=', $value);
    }

    /**
     * @return Collection<int, TModel>
     */
    protected function getPreloadedItems(): Collection {
        return new Collection();
    }
}
