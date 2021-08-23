<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Job;
use App\Services\Queue\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\Concerns\PingableJob
 */
class PingableJobTest extends TestCase {
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
class StoppableJob_Job extends Job {
    public function __invoke(): void {
        // empty
    }

    public function displayName(): string {
        return $this::class;
    }
}
