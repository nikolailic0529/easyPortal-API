<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\Service as BaseService;
use Carbon\CarbonInterval;
use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Date;
use RuntimeException;

use function max;
use function min;
use function mt_getrandmax;
use function mt_rand;

class Service extends BaseService {
    protected const DEFAULT_TTL    = 'P1W';
    protected const MARKER_EXPIRED = '_expired';

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

        try {
            return $lock->block((int) $wait->totalSeconds, $closure);
        } catch (LockTimeoutException) {
            return $closure();
        } finally {
            $lock->forceRelease();
        }
    }

    public function isSlowQuery(float $time): bool {
        $threshold = $this->config->get('ep.cache.graphql.threshold');
        $slow      = $threshold === null || $threshold <= 0 || $time >= $threshold;

        return $slow;
    }

    public function getCacheTtl(): DateInterval {
        return $this->getDefaultTtl() ?? new DateInterval(static::DEFAULT_TTL);
    }

    public function markCacheExpired(): static {
        $this->set(static::MARKER_EXPIRED, Date::now());

        return $this;
    }

    public function isCacheExpired(DateTimeInterface $created, DateTimeInterface $expire): bool {
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
        $expired = $this->getCacheExpired();

        if (!$expired || $created > $expired) {
            return false;
        }

        // Expiring?
        $expiration = $this->config->get('ep.cache.graphql.lifetime_expiration') ?: 'PT1H';
        $expiring   = $minimal->add($expiration);

        return $this->isExpiring($minimal, $expiring);
    }

    protected function getCacheExpired(): ?DateTimeInterface {
        return $this->get(static::MARKER_EXPIRED, static function (string $date): ?DateTimeInterface {
            return Date::make($date);
        });
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

    protected function getDefaultTtl(): ?DateInterval {
        return new DateInterval($this->config->get('ep.cache.graphql.ttl') ?: static::DEFAULT_TTL);
    }

    private function getLockProvider(): ?LockProvider {
        // Enabled?
        $enabled = $this->config->get('ep.cache.graphql.lock_enabled') ?? true;

        if (!$enabled) {
            return null;
        }

        // Supported?
        $store = $this->cache->getStore();

        if (!($store instanceof LockProvider)) {
            throw new RuntimeException('Atomic Locks is not available.');
        }

        // Return
        return $store;
    }
}
