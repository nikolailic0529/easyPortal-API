<?php declare(strict_types = 1);

namespace App\Services\Queue\Concerns;

use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Services\Queue\Exceptions\JobStopped;
use App\Services\Queue\Job;
use App\Services\Queue\Pinger;
use App\Services\Queue\Progress;
use App\Services\Service;
use App\Utils\Iterators\Contracts\ObjectIterator;
use App\Utils\Iterators\ObjectsIterator;
use App\Utils\Processor\Processor;
use App\Utils\Processor\State;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\ConfigurableQueueable;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
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
            ->times(5);

        $service = Mockery::mock(Service::class);
        $service
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        $service
            ->shouldReceive('set')
            ->twice()
            ->andReturnUsing(static function (mixed $key, mixed $value): mixed {
                return $value;
            });
        $service
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        $pinger = Mockery::mock(Pinger::class);
        $pinger
            ->shouldReceive('ping')
            ->twice()
            ->andReturns();

        $job = new class($service, $processor) extends CronJob implements Progressable {
            /**
             * @phpstan-use ProcessorJob<Processor<mixed, mixed, \App\Utils\Processor\State>>
             */
            use ProcessorJob;

            public function __construct(
                protected Service $service,
                protected Processor $processor,
            ) {
                parent::__construct();
            }

            public function displayName(): string {
                return 'test';
            }

            protected function getService(Container $container): ?Service {
                return $this->service;
            }

            protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
                return $this->processor;
            }
        };

        $job->handle($this->app, $pinger);
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeStopped(): void {
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

        $service = Mockery::mock(Service::class);
        $pinger  = Mockery::mock(Pinger::class);
        $pinger
            ->shouldReceive('ping')
            ->once()
            ->andReturns();
        $pinger
            ->shouldReceive('ping')
            ->once()
            ->andThrow(new JobStopped());

        $job = new class($service, $processor) extends Job {
            /**
             * @phpstan-use ProcessorJob<Processor<mixed, mixed, \App\Utils\Processor\State>>
             */
            use ProcessorJob;

            public function __construct(
                protected Service $service,
                protected Processor $processor,
            ) {
                parent::__construct();
            }

            public function displayName(): string {
                return 'test';
            }

            protected function getService(Container $container): ?Service {
                return $this->service;
            }

            protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
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

        $job = Mockery::mock(ProcessorJobTest__ProcessorJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('getProcessor')
            ->once()
            ->andReturn($processor);

        self::assertEquals(
            new Progress($total, $processed),
            ($job->getProgressCallback())($this->app, $this->app->make(QueueableConfigurator::class)),
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

        $job = Mockery::mock(ProcessorJobTest__ProcessorJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('getProcessor')
            ->once()
            ->andReturn($processor);

        self::assertTrue(
            ($job->getResetProgressCallback())($this->app, $this->app->make(QueueableConfigurator::class)),
        );
    }
}

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @extends Processor<int, mixed, State>
 */
abstract class ProcessorJobTest__Processor extends Processor {
    protected function getTotal(State $state): ?int {
        return 5;
    }

    protected function getIterator(State $state): ObjectIterator {
        return new ObjectsIterator(
            Mockery::mock(ExceptionHandler::class),
            [1, 2, 3, 4, 5],
        );
    }

    /**
     * @inheritDoc
     */
    protected function getOnChangeEvent(State $state, array $items, mixed $data): ?object {
        return null;
    }
}

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 *
 * @see          https://github.com/mockery/mockery/issues/1022
 */
abstract class ProcessorJobTest__ProcessorJob extends Job implements ConfigurableQueueable {
    /**
     * @phpstan-use ProcessorJob<Processor<mixed, mixed, \App\Utils\Processor\State>>
     */
    use ProcessorJob;
}
