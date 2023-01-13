<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Models\Customer;
use App\Services\Recalculator\Queue\Tasks\ModelRecalculate;
use App\Services\Recalculator\Queue\Tasks\ModelsRecalculate;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Recalculator\Recalculator
 */
class RecalculatorTest extends TestCase {
    public function testDispatchModel(): void {
        $model   = Customer::factory()->make();
        $service = Mockery::mock(Service::class);
        $indexer = new class($service) extends Recalculator {
            public function dispatchModel(string $model, int|string $key): void {
                parent::dispatchModel($model, $key);
            }
        };

        Queue::fake();

        $indexer->dispatchModel($model::class, $model->getKey());

        Queue::assertPushed(ModelRecalculate::class, static function (ModelRecalculate $task) use ($model): bool {
            self::assertEquals($model->getKey(), $task->getKey());

            return true;
        });
    }

    public function testDispatchModels(): void {
        $modelA  = Customer::factory()->make();
        $modelB  = Customer::factory()->make();
        $service = Mockery::mock(Service::class);
        $indexer = new class($service) extends Recalculator {
            /**
             * @inheritDoc
             */
            public function dispatchModels(string $model, array $keys): void {
                parent::dispatchModels($model, $keys);
            }
        };

        Queue::fake();

        $indexer->dispatchModels($modelA::class, [$modelA->getKey(), $modelB->getKey()]);

        Queue::assertPushed(
            ModelsRecalculate::class,
            static function (ModelsRecalculate $task) use ($modelA, $modelB): bool {
                self::assertEquals([$modelA->getKey(), $modelB->getKey()], $task->getKeys());

                return true;
            },
        );
    }
}
