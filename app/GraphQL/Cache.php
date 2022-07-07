<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\CurrentLocale;
use App\Services\Organization\CurrentOrganization;
use Carbon\CarbonInterval;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Cache\Lock;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Date;
use Throwable;

use function array_merge;
use function is_array;
use function max;
use function min;
use function mt_getrandmax;
use function mt_rand;

class Cache {
    protected const DEFAULT_TTL    = 'P1W';
    protected const MARKER_EXPIRED = '_expired';

    private ?CacheContract $cache = null;

    public function __construct(
        protected ExceptionHandler $exceptionHandler,
        protected ConfigContract $config,
        protected Service $service,
        protected CurrentOrganization $organization,
        protected CurrentLocale $locale,
        CacheFactory $cache,
    ) {
        if ($this->config->get('ep.cache.graphql.enabled') ?? true) {
            $this->cache = $this->getStore($cache);
        }
    }

    public function get(mixed $key): mixed {
        $value = null;

        try {
            $value = $this->cache?->get($this->getKey($key));
        } catch (Throwable) {
            // If `unserialize()` fail it is not critical and should not break
            // anything.
        }

        return $value;
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @return T
     */
    public function set(mixed $key, mixed $value): mixed {
        try {
            $this->cache?->set($this->getKey($key), $value, $this->getTtl());
        } catch (Throwable $exception) {
            $this->exceptionHandler->report($exception);
        }

        return $value;
    }

    public function delete(mixed $key): bool {
        return $this->cache === null
            || $this->cache->delete($this->getKey($key));
    }

    public function has(mixed $key): bool {
        return $this->cache !== null
            && $this->cache->has($this->getKey($key));
    }

    public function isEnabled(): bool {
        return $this->cache !== null;
    }

    public function isLocked(mixed $key): bool {
        // Possible?
        $provider = $this->getLockProvider();

        if (!$provider) {
            return false;
        }

        // Locked?
        $key      = $this->getKey($key);
        $lock     = $provider->lock($key);
        $acquired = $lock->get(static fn(): bool => true);
        $isLocked = $acquired === false;

        return $isLocked;
    }

    /**
     * @template T
     *
     * @param Closure(): T $closure
     *
     * @return T
     */
    public function lock(mixed $key, Closure $closure): mixed {
        // Possible?
        $provider = $this->getLockProvider();

        if (!$provider) {
            return $closure();
        }

        // Lock
        $key  = $this->getKey($key);
        $time = new CarbonInterval($this->config->get('ep.cache.graphql.lock_timeout') ?: 'PT30S');
        $wait = new CarbonInterval($this->config->get('ep.cache.graphql.lock_wait') ?: 'PT35S');
        $lock = $provider->lock($key, (int) $time->totalSeconds);

        if ($lock instanceof Lock) {
            $lock->betweenBlockedAttemptsSleepFor($this->getLockSleepTimeMs());
        }

        try {
            return $lock->block((int) $wait->totalSeconds, $closure);
        } catch (LockTimeoutException) {
            return $closure();
        } finally {
            $lock->forceRelease();
        }
    }

    public function isQueryWasLocked(?float $time): bool {
        $threshold = $this->getLockSleepTimeMs();
        $slept     = $threshold <= 0 || ($time !== null && $time >= $threshold);

        return $slept;
    }

    public function isQuerySlow(?float $time): bool {
        $threshold = $this->config->get('ep.cache.graphql.threshold');
        $slow      = $threshold === null || $threshold <= 0 || ($time !== null && $time >= $threshold);

        return $slow;
    }

    public function isQueryLockable(?float $time): bool {
        $threshold = $this->config->get('ep.cache.graphql.lock_threshold');
        $lockable  = $threshold === null || $threshold <= 0 || ($time !== null && $time >= $threshold);

        return $lockable;
    }

    protected function getKey(mixed $key): string {
        return $this->service->getCacheKey(array_merge(
            [
                $this->locale,
                $this->organization,
            ],
            is_array($key) ? $key : [$key],
        ));
    }

    public function getTtl(): DateInterval {
        return new DateInterval($this->config->get('ep.cache.graphql.ttl') ?: static::DEFAULT_TTL);
    }

    protected function getStore(CacheFactory $cache): CacheContract {
        return $cache->store($this->config->get('ep.cache.graphql.store') ?: null);
    }

    public function markExpired(): static {
        try {
            $this->cache?->set(
                $this->service->getCacheKey(static::MARKER_EXPIRED),
                Date::now(),
                $this->getTtl(),
            );
        } catch (Throwable $exception) {
            $this->exceptionHandler->report($exception);
        }

        return $this;
    }

    public function isExpired(DateTimeInterface $created, DateTimeInterface $expire): bool {
        // TTL Expired?
        $current = Date::now();

        if ($current > $expire) {
            return true;
        }

        // TTL Expiring?
        $ttlExpiration = $this->config->get('ep.cache.graphql.ttl_expiration') ?: 'P1D';
        $ttlExpiring   = Date::make($expire)->sub($ttlExpiration);

        if ($this->isExpiring($expire, $ttlExpiring)) {
            return true;
        }

        // Minimal lifetime reached?
        $lifetime = $this->config->get('ep.cache.graphql.lifetime') ?: 'PT1H';
        $minimal  = Date::make($created)->add($lifetime);

        if ($current < $minimal) {
            return false;
        }

        // Expired?
        $expired = $this->getExpired();

        if (!$expired || $created > $expired) {
            return false;
        }

        // Expiring?
        $expiration = $this->config->get('ep.cache.graphql.lifetime_expiration') ?: 'PT1H';
        $expiring   = $minimal->add($expiration);

        return $this->isExpiring($minimal, $expiring);
    }

    protected function getExpired(): ?DateTimeInterface {
        $date = null;

        try {
            $key  = $this->service->getCacheKey(static::MARKER_EXPIRED);
            $date = Date::make($this->cache?->get($key));
        } catch (Throwable) {
            // If `unserialize()` fail it is not critical and should not break
            // anything.
        }

        return $date;
    }

    protected function isExpiring(DateTimeInterface $datetime, DateTimeInterface $expire): bool {
        // Outside interval?
        //      $timestamp            $expiration
        // ---------|----------------------|---------
        // $current |          or          | $current
        // false    |                      | true
        $current    = Date::now();
        $timestamp  = min($datetime, $expire);
        $expiration = max($datetime, $expire);

        if ($current < $timestamp) {
            return false;
        }

        if ($current > $expiration) {
            return true;
        }

        // Inside interval?
        //  $timestamp                    $current                    $expiration
        // -|---------------------------------|---------------------------------|-
        //  | rand(0, 1) < ($current - $timestamp) / ($expiration - $timestamp) |
        $timestamp  = $timestamp->getTimestamp();
        $current    = $current->getTimestamp() - $timestamp;
        $expiration = $expiration->getTimestamp() - $timestamp;
        $threshold  = $current / $expiration;
        $random     = $this->getRandomNumber();

        return $random < $threshold;
    }

    /**
     * Returns random float between 0 and 1.
     */
    protected function getRandomNumber(): float {
        return mt_rand() / mt_getrandmax();
    }

    protected function getLockSleepTimeMs(): int {
        return 250;
    }

    private function getLockProvider(): ?LockProvider {
        // Enabled?
        $enabled = $this->config->get('ep.cache.graphql.lock_enabled') ?? true;

        if (!$enabled) {
            return null;
        }

        // Supported?
        $store = $this->cache?->getStore();

        if (!($store instanceof LockProvider)) {
            $store = null;
        }

        // Return
        return $store;
    }
}
