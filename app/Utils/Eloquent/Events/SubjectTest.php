<?php declare(strict_types = 1);

namespace App\Utils\Eloquent\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Mockery;
use stdClass;
use Tests\TestCase;

use function count;
use function round;

/**
 * @internal
 * @covers \App\Utils\Eloquent\Events\Subject
 */
class SubjectTest extends TestCase {
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
                ->twice()
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

        foreach (Subject::getEvents() as $event) {
            $dispatcher->listen($event, [$subject, '__invoke']);
        }

        $subject->onModelEvent($observer);

        $dispatcher->dispatch('eloquent.created: any model', [$model]);
        $dispatcher->dispatch('eloquent.created: not model', [new stdClass()]);
        $dispatcher->dispatch('eloquent.updated: any model', [$model]);
        $dispatcher->dispatch('eloquent.updated: not model', [new stdClass()]);
        $dispatcher->dispatch('eloquent.deleted: any model', [$model]);
        $dispatcher->dispatch('eloquent.deleted: not model', [new stdClass()]);
    }

    public function testOnModelSaved(): void {
        $model    = Mockery::mock(Model::class);
        $subject  = new Subject();
        $observer = Mockery::mock(OnModelSaved::class);
        $observer
            ->shouldReceive('modelSaved')
            ->with($model)
            ->twice()
            ->andReturnSelf();

        $dispatcher = new Dispatcher();

        foreach (Subject::getEvents() as $event) {
            $dispatcher->listen($event, [$subject, '__invoke']);
        }

        $subject->onModelSaved($observer);

        $dispatcher->dispatch('eloquent.created: any model', [$model]);
        $dispatcher->dispatch('eloquent.created: not model', [new stdClass()]);
        $dispatcher->dispatch('eloquent.updated: any model', [$model]);
        $dispatcher->dispatch('eloquent.updated: not model', [new stdClass()]);
    }

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

        foreach (Subject::getEvents() as $event) {
            $dispatcher->listen($event, [$subject, '__invoke']);
        }

        $subject->onModelDeleted($observer);

        $dispatcher->dispatch('eloquent.deleted: any model', [$model]);
        $dispatcher->dispatch('eloquent.deleted: not model', [new stdClass()]);
    }
}
