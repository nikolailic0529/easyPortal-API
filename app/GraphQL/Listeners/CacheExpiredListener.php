<?php declare(strict_types = 1);

namespace App\GraphQL\Listeners;

use App\Events\Subscriber;
use App\GraphQL\Cache;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Maintenance\Events\VersionUpdated;
use Illuminate\Contracts\Events\Dispatcher;

class CacheExpiredListener implements Subscriber {
    public function __construct(
        protected Cache $cache,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(DataImported::class, $this::class);
        $dispatcher->listen(VersionUpdated::class, $this::class);
    }

    public function __invoke(): void {
        $this->cache->markExpired();
    }
}
