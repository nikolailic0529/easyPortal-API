<?php declare(strict_types = 1);

namespace App\GraphQL\Listeners;

use App\GraphQL\Cache;
use App\GraphQL\Service;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Listeners\DataImportedListener
 */
class CacheExpiredListenerTest extends TestCase {
    /**
     * @covers ::subscribe
     */
    public function testSubscribe(): void {
        $this->override(CacheExpiredListener::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('__invoke')
                ->once()
                ->andReturns();
        });

        $this->app->make(Dispatcher::class)
            ->dispatch(new DataImported(new Data()));
    }

    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $this->override(Cache::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('markExpired')
                ->once()
                ->andReturnSelf();
        });

        $listener = $this->app->make(CacheExpiredListener::class);

        $listener();
    }
}
