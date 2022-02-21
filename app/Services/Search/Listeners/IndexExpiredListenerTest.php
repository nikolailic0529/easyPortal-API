<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Models\Asset;
use App\Models\Customer;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Search\Jobs\UpdateIndexJob;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

use function array_values;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Listeners\IndexExpiredListener
 */
class IndexExpiredListenerTest extends TestCase {
    /**
     * @covers ::subscribe
     */
    public function testSubscribe(): void {
        $this->override(IndexExpiredListener::class, static function (MockInterface $mock): void {
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
        $data     = (new Data())
            ->collect(Asset::factory()->make())
            ->collect(Customer::factory()->make());
        $event    = new DataImported($data);
        $listener = $this->app->make(IndexExpiredListener::class);

        Queue::fake();

        $listener($event);

        Queue::assertPushed(UpdateIndexJob::class, 2);
        Queue::assertPushed(static function (UpdateIndexJob $job) use ($data): bool {
            self::assertEquals($job->getIds(), array_values($data->get($job->getModel())));

            return true;
        });
    }
}
