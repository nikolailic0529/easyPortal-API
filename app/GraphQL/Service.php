<?php declare(strict_types = 1);

namespace App\GraphQL;

use App\Services\I18n\Locale;
use App\Services\Service as BaseService;
use Closure;
use DateInterval;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use RuntimeException;

use function array_merge;

class Service extends BaseService {
    public function __construct(
        Config $config,
        Cache $cache,
        protected Locale $locale,
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
        $time = $this->config->get('ep.cache.graphql.lock') ?: 30;
        $wait = $this->config->get('ep.cache.graphql.wait') ?: ($time + 5);
        $lock = $store->lock($key, $time);

        try {
            return $lock->block($wait, $closure);
        } catch (LockTimeoutException) {
            return $closure();
        } finally {
            $lock->forceRelease();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getDefaultKey(): array {
        return array_merge(parent::getDefaultKey(), [
            // TODO [!] AppVersion,
            $this->locale,
        ]);
    }

    protected function getDefaultTtl(): DateInterval|int|null {
        return new DateInterval($this->config->get('ep.cache.graphql.ttl') ?: 'P1W');
    }
}
