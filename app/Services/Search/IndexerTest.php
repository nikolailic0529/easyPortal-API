<?php declare(strict_types = 1);

namespace App\Services\Search;

use App\Models\Customer;
use App\Services\Search\Queue\Tasks\ModelIndex;
use App\Services\Search\Queue\Tasks\ModelsIndex;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Indexer
 */
class IndexerTest extends TestCase {
    /**
     * @covers ::dispatchModel
     */
    public function testDispatchModel(): void {
        $model   = Customer::factory()->make();
        $indexer = new class($this->app) extends Indexer {
            public function dispatchModel(string $model, int|string $key): void {
                parent::dispatchModel($model, $key);
            }
        };

        Queue::fake();

        $indexer->dispatchModel($model::class, $model->getKey());

        Queue::assertPushed(ModelIndex::class, static function (ModelIndex $task) use ($model): bool {
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
        $indexer = new class($this->app) extends Indexer {
            /**
             * @inheritDoc
             */
            public function dispatchModels(string $model, array $keys): void {
                parent::dispatchModels($model, $keys);
            }
        };

        Queue::fake();

        $indexer->dispatchModels($modelA::class, [$modelA->getKey(), $modelB->getKey()]);

        Queue::assertPushed(ModelsIndex::class, static function (ModelsIndex $task) use ($modelA, $modelB): bool {
            self::assertEquals([$modelA->getKey(), $modelB->getKey()], $task->getKeys());

            return true;
        });
    }
}