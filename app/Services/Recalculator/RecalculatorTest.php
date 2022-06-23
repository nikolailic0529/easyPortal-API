<?php declare(strict_types = 1);

namespace App\Services\Recalculator;

use App\Models\Customer;
use App\Services\Recalculator\Queue\Tasks\ModelRecalculate;
use App\Services\Recalculator\Queue\Tasks\ModelsRecalculate;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Recalculator
 */
class RecalculatorTest extends TestCase {
    /**
     * @covers ::dispatchModel
     */
    public function testDispatchModel(): void {
        $model   = Customer::factory()->make();
        $indexer = new class($this->app) extends Recalculator {
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

    /**
     * @covers ::dispatchModels
     */
    public function testDispatchModels(): void {
        $modelA  = Customer::factory()->make();
        $modelB  = Customer::factory()->make();
        $indexer = new class($this->app) extends Recalculator {
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
