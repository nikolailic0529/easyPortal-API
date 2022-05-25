<?php declare(strict_types = 1);

namespace App\Services\Events\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Mockery;
use stdClass;
use Tests\TestCase;

use function count;
use function round;

/**
 * @internal
 * @coversDefaultClass \App\Services\Events\Eloquent\Subject
 */
class SubjectTest extends TestCase {
    /**
     * @covers ::onModelEvent
     * @covers ::subscribe
     */
    public function testOnModelEvent(): void {
        $model    = Mockery::mock(Model::class);
        $subject  = new Subject();
        $classes  = [OnModelSaved::class, OnModelDeleted::class];
        $classes  = $this->faker->randomElements($classes, round(count($classes) / 2));
        $observer = Mockery::mock(stdClass::class, ...$classes);

        if ($observer instanceof OnModelSaved) {
            $observer
                ->shouldReceive('modelSaved')
                ->with($model)
                ->once()
                ->andReturnSelf();
        }

        if ($observer instanceof OnModelDeleted) {
            $observer
                ->shouldReceive('modelDeleted')
                ->with($model)
                ->once()
                ->andReturnSelf();
        }

        $dispatcher = new Dispatcher();

        $subject->subscribe($dispatcher);
        $subject->onModelEvent($observer);

        $dispatcher->dispatch('eloquent.saved: any model', [$model]);
        $dispatcher->dispatch('eloquent.saved: not model', [new stdClass()]);
        $dispatcher->dispatch('eloquent.deleted: any model', [$model]);
        $dispatcher->dispatch('eloquent.deleted: not model', [new stdClass()]);
    }

    /**
     * @covers ::onModelSaved
     * @covers ::subscribe
     */
    public function testOnModelSaved(): void {
        $model    = Mockery::mock(Model::class);
        $subject  = new Subject();
        $observer = Mockery::mock(OnModelSaved::class);
        $observer
            ->shouldReceive('modelSaved')
            ->with($model)
            ->once()
            ->andReturnSelf();

        $dispatcher = new Dispatcher();

        $subject->subscribe($dispatcher);
        $subject->onModelSaved($observer);

        $dispatcher->dispatch('eloquent.saved: any model', [$model]);
        $dispatcher->dispatch('eloquent.saved: not model', [new stdClass()]);
    }

    /**
     * @covers ::onModelDeleted
     * @covers ::subscribe
     */
    public function testOnModelDeleted(): void {
        $model    = Mockery::mock(Model::class);
        $subject  = new Subject();
        $observer = Mockery::mock(OnModelDeleted::class);
        $observer
            ->shouldReceive('modelDeleted')
            ->with($model)
            ->once()
            ->andReturnSelf();

        $dispatcher = new Dispatcher();

        $subject->subscribe($dispatcher);
        $subject->onModelDeleted($observer);

        $dispatcher->dispatch('eloquent.deleted: any model', [$model]);
        $dispatcher->dispatch('eloquent.deleted: not model', [new stdClass()]);
    }
}
