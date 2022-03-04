<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Models\Asset;
use App\Models\Customer;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Search\Jobs\Index;
use Closure;
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
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::subscribe
     *
     * @dataProvider dataProviderSubscribe
     *
     * @param \Closure(self): object $eventFactory
     */
    public function testSubscribe(Closure $eventFactory): void {
        $this->override(IndexExpiredListener::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('__invoke')
                ->once()
                ->andReturns();
        });

        $this->app->make(Dispatcher::class)
            ->dispatch($eventFactory($this));
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeDataImported(): void {
        $data     = (new Data())
            ->collect(Asset::factory()->make())
            ->collect(Customer::factory()->make());
        $event    = new DataImported($data);
        $listener = $this->app->make(IndexExpiredListener::class);

        Queue::fake();

        $listener($event);

        Queue::assertPushed(Index::class, 2);
        Queue::assertPushed(static function (Index $job) use ($data): bool {
            self::assertEquals($job->getKeys(), array_values($data->get($job->getModel())));

            return true;
        });
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeModelsRecalculated(): void {
        $event    = new ModelsRecalculated(Customer::class, [
            $this->faker->uuid,
            $this->faker->uuid,
        ]);
        $listener = $this->app->make(IndexExpiredListener::class);

        Queue::fake();

        $listener($event);

        Queue::assertPushed(Index::class, 1);
        Queue::assertPushed(static function (Index $job) use ($event): bool {
            self::assertEquals($job->getKeys(), $event->getKeys());

            return true;
        });
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array<\Closure():object>>
     */
    public function dataProviderSubscribe(): array {
        return [
            DataImported::class       => [
                static function (): object {
                    return new DataImported(new Data());
                },
            ],
            ModelsRecalculated::class => [
                static function (self $test): object {
                    return new ModelsRecalculated(Customer::class, [
                        $test->faker->uuid,
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
