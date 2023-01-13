<?php declare(strict_types = 1);

namespace App\Services\Search\Listeners;

use App\Models\Asset;
use App\Models\Customer;
use App\Services\DataLoader\Collector\Data;
use App\Services\DataLoader\Events\DataImported;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Services\Search\Indexer;
use App\Services\Search\Service;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Search\Listeners\IndexExpiredListener
 */
class IndexExpiredListenerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderSubscribe
     *
     * @param Closure(self): object $eventFactory
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

    public function testInvokeDataImported(): void {
        $data  = (new Data())
            ->collect(Asset::factory()->make())
            ->collect(Customer::factory()->make());
        $event = new DataImported($data);

        $this->override(Service::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isSearchableModel')
                ->atLeast()
                ->once()
                ->andReturns(true);
        });

        $this->override(Indexer::class, static function (MockInterface $mock) use ($data): void {
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

        $listener = $this->app->make(IndexExpiredListener::class);

        $listener($event);
    }

    public function testInvokeModelsRecalculated(): void {
        $model = Customer::class;
        $keys  = [
            $this->faker->uuid(),
            $this->faker->uuid(),
        ];
        $event = new ModelsRecalculated($model, $keys);

        $this->override(Indexer::class, static function (MockInterface $mock) use ($model, $keys): void {
            $mock
                ->shouldReceive('dispatch')
                ->with([
                    'model' => $model,
                    'keys'  => $keys,
                ])
                ->once()
                ->andReturns();
        });

        $listener = $this->app->make(IndexExpiredListener::class);

        $listener($event);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,array<Closure():object>>
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
                        $test->faker->uuid(),
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
