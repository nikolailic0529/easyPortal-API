<?php declare(strict_types = 1);

namespace App\Services;

use App\Utils\CacheKey;
use App\Utils\CacheKeyable;
use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use JsonSerializable;

use function array_merge;
use function array_slice;
use function class_exists;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_object;
use function json_decode;
use function json_encode;
use function str_starts_with;

use const JSON_THROW_ON_ERROR;

/**
 * Wrapper around the {@see \Illuminate\Contracts\Cache\Repository} that
 * standardizes keys across all services.
 */
abstract class Service implements CacheKeyable {
    /**
     * @param \Illuminate\Contracts\Cache\Repository&\Illuminate\Cache\TaggableStore $cache
     */
    public function __construct(
        protected Config $config,
        protected Cache $cache,
    ) {
        // empty
    }

    /**
     * Returns the cached value for the key and passes it into `$factory` if key
     * exists.
     *
     * @template T
     *
     * @param    (\Closure(mixed):T)|null $factory
     *
     * @return T
     */
    public function get(mixed $key, Closure $factory = null): mixed {
        $value = $this->cache->get($this->getKey($key));

        if ($value !== null) {
            $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

            if ($factory) {
                $value = $factory($value);
            }
        }

        return $value;
    }

    /**
     * Sets the value for the key. The method also sets the TTL to automatically
     * remove old unused keys from the cache.
     *
     * @param \JsonSerializable|array<mixed>|string|float|int|bool|null $value
     * @param array<string>                                             $tags
     */
    public function set(
        mixed $key,
        JsonSerializable|array|string|float|int|bool|null $value,
        array $tags = [],
    ): mixed {
        ($tags ? $this->cache->tags($tags) : $this->cache)
            ->set($this->getKey($key), json_encode($value), $this->getDefaultTtl());

        return $value;
    }

    public function delete(mixed $key): bool {
        return $this->cache->delete($this->getKey($key));
    }

    /**
     * @param array<string> $tags
     */
    public function flush(array $tags): bool {
        return $this->cache->tags($tags)->flush();
    }

    public function has(mixed $key): bool {
        return $this->cache->has($this->getKey($key));
    }

    protected function getKey(mixed $key): string {
        $key = array_merge([$this], $this->getDefaultKey(), is_array($key) ? $key : [$key]);
        $key = new CacheKey($key);

        return (string) $key;
    }

    /**
     * @return array<mixed>
     */
    protected function getDefaultKey(): array {
        return [];
    }

    protected function getDefaultTtl(): DateInterval|int|null {
        return new DateInterval($this->config->get('ep.cache.service.ttl') ?: 'P6M');
    }

    /**
     * @param object|class-string $class
     *
     * @return class-string<\App\Services\Service>|null
     */
    public static function getService(object|string $class): ?string {
        $class   = is_object($class) ? $class::class : $class;
        $parts   = array_slice(explode('\\', self::class), 0, -1);
        $service = null;

        if (str_starts_with($class, implode('\\', $parts))) {
            $service = implode('\\', array_slice(explode('\\', $class), 0, count($parts) + 1)).'\\Service';

            if (!class_exists($service)) {
                $service = null;
            }
        }

        return $service;
    }
}
