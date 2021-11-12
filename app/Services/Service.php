<?php declare(strict_types = 1);

namespace App\Services;

use App\Services\Queue\NamedJob;
use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
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
use function sprintf;
use function str_starts_with;

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
     * @param array<object|string>|object|string $key
     * @param    (\Closure():T)|null $factory
     *
     * @return T
     */
    public function get(object|array|string $key, Closure $factory = null): mixed {
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
     * Sets the value for the key. The method also sets the TTL to automatically
     * remove old unused keys from the cache.
     *
     * @param array<object|string>|object|string                        $key
     * @param \JsonSerializable|array<mixed>|string|float|int|bool|null $value
     */
    public function set(object|array|string $key, JsonSerializable|array|string|float|int|bool|null $value): mixed {
        $this->cache->set($this->getKey($key), json_encode($value), $this->getDefaultTtl());

        return $value;
    }

    /**
     * @param array<object|string>|object|string $key
     */
    public function delete(object|array|string $key): bool {
        return $this->cache->delete($this->getKey($key));
    }

    /**
     * @param array<object|string>|object|string $key
     */
    public function has(object|array|string $key): bool {
        return $this->cache->has($this->getKey($key));
    }

    /**
     * @param array<object|string>|object|string $key
     */
    protected function getKey(object|array|string $key): string {
        $parts = [$this::class];

        if (!is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $value) {
            $parts[] = $this->getKeyPart($value);
        }

        return implode(':', $parts);
    }

    /**
     * @return array<string>
     */
    protected function getKeyPart(object|string $value): string {
        $part = '';

        if ($value instanceof NamedJob) {
            $part = $value->displayName();
        } elseif ($value instanceof Model) {
            if (!$value->exists || !$value->getKey()) {
                throw new InvalidArgumentException(sprintf(
                    'The instance of `%s` should exist and have a non-empty key.',
                    $value::class,
                ));
            }

            $part = "{$value->getMorphClass()}:{$value->getKey()}";
        } elseif (is_object($value)) {
            $part = $value::class;
        } else {
            $part = $value;
        }

        return $part;
    }

    protected function getDefaultTtl(): DateInterval|int|null {
        return new DateInterval('P1M');
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
