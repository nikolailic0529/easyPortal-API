<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Listeners;

use App\Models\Asset;
use App\Models\Customer;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Recalculator;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Recalculator\Listeners\DataImportedListener
 */
class DataImportedListenerTest extends TestCase {
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

    public function testInvoke(): void {
        $data  = (new Data())
            ->collect(Asset::factory()->make())
            ->collect(Customer::factory()->make());
        $event = new DataImported($data);

        $this->override(Recalculator::class, static function (MockInterface $mock) use ($data): void {
            foreach ($data->getData() as $model => $keys) {
                $mock
                    ->shouldReceive('dispatch')
                    ->with([
                        'model' => $model,
                        'keys'  => $keys,
                    ])
                    ->once()
                    ->andReturns();
            }
        });

        $listener = $this->app->make(DataImportedListener::class);

        $listener($event);
    }
}
