<?php declare(strict_types = 1);

namespace App\Services;

use App\GraphQL\Service as GraphQLService;
use App\Queues;
use App\Utils\Cache\CacheKey;
use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
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
abstract class Service {
    protected Cache $cache;

    public function __construct(
        protected Config $config,
        CacheFactory $factory,
    ) {
        $this->cache = $factory->store($this->config->get('ep.cache.service.store') ?: null);
    }

    /**
     * Returns the cached state value for the key and passes it into `$factory`
     * if key exists.
     *
     * @template T
     *
     * @param    (\Closure(mixed):T)|null $factory
     *
     * @return T
     */
    public function get(mixed $key, Closure $factory = null): mixed {
        $value = $this->cache->get($this->getCacheKey($key));

        if ($value !== null) {
            $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

            if ($factory) {
                $value = $factory($value);
            }
        }

        return $value;
    }

    /**
     * Sets the state value for the key. The method also sets the TTL to
     * automatically remove old unused keys from the cache.
     *
     * @param \JsonSerializable|array<mixed>|string|float|int|bool|null $value
     */
    public function set(mixed $key, JsonSerializable|array|string|float|int|bool|null $value): mixed {
        $this->cache->set($this->getCacheKey($key), json_encode($value), $this->getDefaultTtl());

        return $value;
    }

    /**
     * Deletes the state value.
     */
    public function delete(mixed $key): bool {
        return $this->cache->delete($this->getCacheKey($key));
    }

    /**
     * Checks if the state value exists.
     */
    public function has(mixed $key): bool {
        return $this->cache->has($this->getCacheKey($key));
    }

    public function getCacheKey(mixed $key): string {
        return (string) new CacheKey(array_merge(
            ['app', static::getServiceName($this)],
            is_array($key) ? $key : [$key],
        ));
    }

    protected function getDefaultTtl(): ?DateInterval {
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
        } elseif (str_starts_with($class, implode('\\', array_slice(explode('\\', GraphQLService::class), 0, -1)))) {
            $service = GraphQLService::class;
        } else {
            // empty
        }

        return $service;
    }

    /**
     * @param object|class-string $class
     */
    public static function getServiceName(object|string $class): ?string {
        $service = static::getService($class);
        $name    = $service
            ? array_slice(explode('\\', $service), -2, 1)[0]
            : null;

        return $name;
    }

    public static function getDefaultQueue(): string {
        return Queues::DEFAULT;
    }
}
