<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Models\Customer;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Jobs\CustomerRecalculate;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

use function count;

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
            ->collect(Customer::factory()->count(2)->make());
        $keys     = $data->get(Customer::class);
        $event    = new DataImported($data);
        $listener = $this->app->make(DataImportedListener::class);

        Queue::fake();

        $listener($event);

        Queue::assertPushed(CustomerRecalculate::class, count($keys));

        foreach ($keys as $key) {
            Queue::assertPushed(static function (CustomerRecalculate $job) use ($key): bool {
                return $job->getModelKey() === $key;
            });
        }
    }
}
