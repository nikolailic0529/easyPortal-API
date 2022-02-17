<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Models\Customer;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Jobs\CustomersRecalculate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Listeners\DataImportedListener
 */
class DataImportedListenerTest extends TestCase {
    /**
     * @covers ::subscribe
     */
    public function testSubscribe(): void {
        $this->override(DataImportedListener::class, static function (MockInterface $mock): void {
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
            ->collect(Customer::factory()->make());
        $event    = new DataImported($data);
        $listener = $this->app->make(DataImportedListener::class);

        Queue::fake();

        $listener($event);

        Queue::assertPushed(CustomersRecalculate::class, 1);
        Queue::assertPushed(static function (CustomersRecalculate $job) use ($data): bool {
            self::assertEquals($job->getKeys(), $data->get(Customer::class));

            return true;
        });
    }
}
