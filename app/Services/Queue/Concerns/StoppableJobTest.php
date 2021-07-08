<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Queue;
use App\Services\Queue\Stoppable;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\Concerns\StoppableJob
 */
class StoppableJobTest extends TestCase {
    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $job = Mockery::mock(StoppableJob_Job::class);
        $job->makePartial();
        $job
            ->shouldReceive('__invoke')
            ->once()
            ->andReturns();

        $this->override(Queue::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isStopped')
                ->once()
                ->andReturn(false);
        });

        $job->run();
    }

    /**
     * @covers ::handle
     */
    public function testHandleStopped(): void {
        $job = Mockery::mock(StoppableJob_Job::class);
        $job->makePartial();
        $job
            ->shouldReceive('__invoke')
            ->never();

        $this->override(Queue::class, static function (MockInterface $mock): void {
            $mock
                ->shouldReceive('isStopped')
                ->once()
                ->andReturn(true);
        });

        $job->run();
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class StoppableJob_Job extends Job implements Stoppable {
    use StoppableJob;

    public function __invoke(): void {
        // empty
    }
}
