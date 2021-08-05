<?php declare(strict_types = 1);

namespace App\Services;

use App\Services\Queue\NamedJob;
use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\Repository;
use JsonSerializable;

use function implode;
use function is_object;
use function json_decode;
use function json_encode;

/**
 * Wrapper around the {@see \Illuminate\Contracts\Cache\Repository} that
 * standardizes keys across all services.
 */
abstract class Service {
    public function __construct(
        protected Repository $cache,
    ) {
        // empty
    }

    /**
     * Returns the cached value for the key and passes it into `$factory` if key
     * exists. The method also reset TTL.
     *
     * @template T
     *
     * @param (\Closure():T)|null $factory
     *
     * @return T
     */
    public function get(object|string $key, Closure $factory = null): mixed {
        $value = null;

        if ($this->has($key)) {
            $value = json_decode($this->cache->get($this->getKey($key), $value), true);
            $value = $this->set($key, $value);

            if ($factory) {
                $value = $factory($value);
            }
        }

        return $value;
    }

    /**
     * @param \JsonSerializable|array<mixed>|string|float|int|bool|null $value
     */
    public function set(object|string $key, JsonSerializable|array|string|float|int|bool|null $value): mixed {
        $this->cache->set($this->getKey($key), json_encode($value), $this->getDefaultTtl());

        return $value;
    }

    public function delete(object|string $key): bool {
        return $this->cache->delete($this->getKey($key));
    }

    public function has(object|string $key): bool {
        return $this->cache->has($this->getKey($key));
    }

    protected function getKey(object|string $key): string {
        $parts = [$this::class];

        if ($key instanceof NamedJob) {
            $parts[] = $key->displayName();
        } elseif (is_object($key)) {
            $parts[] = $key::class;
        } else {
            $parts[] = $key;
        }

        return implode(':', $parts);
    }

    protected function getDefaultTtl(): DateInterval|int|null {
        return new DateInterval('P1M');
    }
}
