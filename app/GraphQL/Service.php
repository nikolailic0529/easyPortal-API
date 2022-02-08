<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\Service as BaseService;
use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use RuntimeException;

class Service extends BaseService {
    public function __construct(
        Config $config,
        Cache $cache,
    ) {
        parent::__construct($config, $cache);
    }

    public function lock(mixed $key, Closure $closure): mixed {
        // Possible?
        $store = $this->cache->getStore();

        if (!($store instanceof LockProvider)) {
            throw new RuntimeException('Atomic Locks is not available.');
        }

        // Lock
        $key  = $this->getKey($key);
        $time = ((int) $this->config->get('ep.cache.graphql.lock')) ?: 30;
        $wait = ((int) $this->config->get('ep.cache.graphql.wait')) ?: ($time + 5);
        $lock = $store->lock($key, $time);

        try {
            return $lock->block($wait, $closure);
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

    protected function getDefaultTtl(): DateInterval|int|null {
        return new DateInterval($this->config->get('ep.cache.graphql.ttl') ?: 'P1W');
    }
}
