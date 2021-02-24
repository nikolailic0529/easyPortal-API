<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Models\Model;
use App\Services\DataLoader\Cache\Cache;
use App\Services\DataLoader\Cache\ModelKey;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\Pure;

abstract class Provider {
    protected Cache|null $cache = null;

    public function __construct() {
        // empty
    }

    protected function resolve(mixed $key, Closure $creator = null, Closure $resolver = null): Model {
        // Model already inside cache?
        if ($this->getCache()->has($key)) {
            return $this->getCache()->get($key);
        }

        // Nope. Trying to find or create it
        $model = ($resolver ? $resolver() : null) ?? ($creator ? $creator() : null);

        if ($model) {
            $this->getCache()->put($model);
        }

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
