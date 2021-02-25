<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Model;
use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\ModelKey;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

/**
 * The provider performs a search of the model with given properties in the
 * database and returns it or creates a new one if it does not exist. It also
 * implements advanced cache practices to reduce the number of database lookups.
 */
abstract class Provider {
    protected Cache|null $cache = null;
    protected Normalizer $normalizer;

    public function __construct(Normalizer $normalizer) {
        $this->normalizer = $normalizer;
    }

    protected function resolve(mixed $key, Closure ...$resolvers): ?Model {
        // Model already inside cache?
        if ($this->getCache()->has($key)) {
            return $this->getCache()->get($key);
        }

        // Nope. Trying to resolve
        $cache = $this->getCache();
        $model = null;

        foreach ($resolvers as $resolver) {
            $model = $resolver();

            if ($model) {
                break;
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
    protected function getInitialQuery(): ?Builder {
        return null;
    }
}
