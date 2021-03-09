<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Model;
use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\ModelKey;
use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Container\Singleton;
use App\Services\DataLoader\Exceptions\FactoryObjectNotFoundException;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

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
abstract class Provider implements Isolated, Singleton {
    protected Cache|null $cache = null;
    protected Normalizer $normalizer;

    public function __construct(Normalizer $normalizer) {
        $this->normalizer = $normalizer;
    }

    protected function resolve(mixed $key, Closure $factory = null): ?Model {
        // Model already in cache or can be found?
        $key   = $this->normalizer->key($key);
        $model = $this->getCache()->has($key)
            ? $this->getCache()->get($key)
            : $this->getFindQuery($key)?->first();
        $cache = $this->getCache();

        // Not found? Well, maybe we can create?
        if (!$model && $factory) {
            try {
                $model = $factory($this->normalizer);
            } catch (FactoryObjectNotFoundException $exception) {
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

    protected function getCache(): Cache {
        if (!$this->cache) {
            $query       = $this->getInitialQuery();
            $items       = $query ? $query->get() : new Collection();
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
    protected function getFindQuery(mixed $key): ?Builder {
        return null;
    }

    #[Pure]
    protected function getInitialQuery(): ?Builder {
        return null;
    }
}
