<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Processor;

use App\Models\Customer;
use App\Services\Recalculator\Events\ModelsRecalculated;
use App\Utils\Eloquent\Events\Subject;
use App\Utils\Processor\EloquentState;
use App\Utils\Processor\State;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Recalculator\Processor\Processor
 */
class ProcessorTest extends TestCase {
    public function testWillNotDispatchEventIfNoChanges(): void {
        $subject    = $this->app->make(Subject::class);
        $handler    = $this->app->make(ExceptionHandler::class);
        $config     = $this->app->make(Repository::class);
        $dispatcher = Event::fake(ModelsRecalculated::class);
        $processor  = new /** @extends Processor<Customer, ChunkData<Customer>, EloquentState<Customer>> */ class(
            $handler,
            $dispatcher,
            $config,
            $subject,
        ) extends Processor {
            protected function getModel(): string {
                return Customer::class;
            }

            /**
             * @inheritDoc
             */
            protected function prefetch(State $state, array $items): mixed {
                return new ChunkData($items);
            }

            protected function process(State $state, mixed $data, mixed $item): void {
                // empty
            }
        };

        $processor
            ->setKeys([
                Customer::factory()->create()->getKey(),
            ])
            ->start();

        $dispatcher->assertNothingDispatched();
    }

    public function testWillDispatchEventIfChanges(): void {
        $subject    = $this->app->make(Subject::class);
        $handler    = $this->app->make(ExceptionHandler::class);
        $config     = $this->app->make(Repository::class);
        $dispatcher = Event::fake(ModelsRecalculated::class);
        $processor  = new /** @extends Processor<Customer, ChunkData<Customer>, EloquentState<Customer>> */ class(
            $handler,
            $dispatcher,
            $config,
            $subject,
        ) extends Processor {
            protected function getModel(): string {
                return Customer::class;
            }

            /**
             * @inheritDoc
             */
            protected function prefetch(State $state, array $items): mixed {
                return new ChunkData($items);
            }

            protected function process(State $state, mixed $data, mixed $item): void {
                $item->name = 'new name';
                $item->save();
            }
        };

        $processor
            ->setKeys([
                Customer::factory()->create()->getKey(),
            ])
            ->start();

        $dispatcher->assertDispatched(ModelsRecalculated::class);
    }
}
