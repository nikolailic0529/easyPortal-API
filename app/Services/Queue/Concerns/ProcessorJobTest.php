<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Exceptions\JobStopped;
use App\Services\Queue\Job;
use App\Services\Queue\Pinger;
use App\Services\Queue\Progress;
use App\Utils\Iterators\ObjectIterator;
use App\Utils\Iterators\OneChunkOffsetBasedObjectIterator;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\Concerns\ProcessorJob
 */
class ProcessorJobTest extends TestCase {
    /**
     * @covers ::__invoke
     */
    public function testInvoke(): void {
        $processor = Mockery::mock(ProcessorJobTest__Processor::class, [
            $this->app->make(ExceptionHandler::class),
            $this->app->make(Dispatcher::class),
            null,
        ]);
        $processor->shouldAllowMockingProtectedMethods();
        $processor->makePartial();
        $processor
            ->shouldReceive('prefetch')
            ->once();
        $processor
            ->shouldReceive('process')
            ->twice();

        $pinger = Mockery::mock(Pinger::class);
        $pinger
            ->shouldReceive('ping')
            ->once()
            ->andReturns();
        $pinger
            ->shouldReceive('ping')
            ->once()
            ->andThrow(new JobStopped());

        $job = new class($processor) extends Job {
            use ProcessorJob;

            public function __construct(
                protected Processor $processor,
            ) {
                parent::__construct();
            }

            public function displayName(): string {
                return 'test';
            }

            protected function getProcessor(Container $container): Processor {
                return $this->processor;
            }
        };

        $this->setQueueableConfig($job, [
            'settings' => [
                'chunk' => 2,
            ],
        ]);

        $job->handle($this->app, $pinger);
    }

    /**
     * @covers ::getProgressCallback
     */
    public function testGetProgressCallback(): void {
        $total     = $this->faker->randomNumber();
        $processed = $this->faker->randomNumber();
        $state     = State::make(['total' => $total, 'processed' => $processed]);
        $processor = Mockery::mock(Processor::class);
        $processor
            ->shouldReceive('getState')
            ->once()
            ->andReturn($state);

        $job = Mockery::mock(ProcessorJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('getProcessor')
            ->once()
            ->andReturn($processor);

        $this->assertEquals(
            new Progress($total, $processed),
            ($job->getProgressCallback())($this->app),
        );
    }

    /**
     * @covers ::getResetProgressCallback
     */
    public function testGetResetProgressCallback(): void {
        $processor = Mockery::mock(Processor::class);
        $processor
            ->shouldReceive('reset')
            ->once();

        $job = Mockery::mock(ProcessorJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('getProcessor')
            ->once()
            ->andReturn($processor);

        $this->assertTrue(
            ($job->getResetProgressCallback())($this->app),
        );
    }
}


// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
abstract class ProcessorJobTest__Processor extends Processor {
    protected function getTotal(): ?int {
        return 5;
    }

    protected function getIterator(): ObjectIterator {
        return new OneChunkOffsetBasedObjectIterator(static function (): array {
            return [1, 2, 3, 4, 5];
        });
    }

    /**
     * @inheritDoc
     */
    protected function getOnChangeEvent(State $state, array $items): ?object {
        return null;
    }
}
