<?php declare(strict_types = 1);

namespace App\GraphQL\Listeners;

use App\Events\Subscriber;
use App\GraphQL\Service;
use App\Services\DataLoader\Events\DataImported;
use Illuminate\Contracts\Events\Dispatcher;

class CacheExpiredListener implements Subscriber {
    public function __construct(
        protected Service $service,
    ) {
        // empty
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(DataImported::class, $this::class);
    }

    public function __invoke(): void {
        $this->service->markCacheExpired();
    }
}
