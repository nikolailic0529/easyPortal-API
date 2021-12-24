<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Importer\Status;
use App\Services\KeyCloak\Importer\UsersImporter;
use App\Services\Queue\Progress;
use App\Utils\Iterators\OffsetBasedObjectIterator;
use Closure;
use EmptyIterator;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Iterator;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Jobs\SyncUsersCronJob
 */
class SyncUsersCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(SyncUsersCronJob::class);
    }

    /**
     * @covers ::process
     */
    public function testProcess(): void {
        $status       = new Status();
        $service      = $this->app->make(Service::class);
        $configurator = $this->app->make(QueueableConfigurator::class);

        $iterator = Mockery::mock(OffsetBasedObjectIterator::class);
        $iterator->shouldAllowMockingProtectedMethods();
        $iterator->makePartial();
        $iterator
            ->shouldReceive('getIterator')
            ->twice()
            ->andReturnUsing(static function (): Iterator {
                return new EmptyIterator();
            });

        $this->override(Client::class, static function (MockInterface $mock) use ($iterator): void {
            $mock->shouldAllowMockingProtectedMethods();
            $mock
                ->shouldReceive('call')
                ->never();
            $mock
                ->shouldReceive('getUsersIterator')
                ->once()
                ->andReturn($iterator);
        });

        /** @var \Mockery\MockInterface $job */
        $job = Mockery::mock(SyncUsersCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        $job
            ->shouldReceive('ping')
            ->once();
        $job
            ->shouldReceive('updateState')
            ->with($service, Mockery::type(SyncUserState::class), $status)
            ->twice()
            ->andReturns();
        $job
            ->shouldReceive('resetState')
            ->with($service)
            ->once()
            ->andReturns();

        $client   = $this->app->make(Client::class);
        $config   = $this->app->make(Repository::class);
        $handler  = $this->app->make(ExceptionHandler::class);
        $importer = Mockery::mock(UsersImporter::class, [$handler, $config, $client]);
        $importer->shouldAllowMockingProtectedMethods();
        $importer->makePartial();

        $importer
            ->shouldReceive('onInit')
            ->withArgs(function (?Closure $closure) use ($status): bool {
                $this->assertNotNull($closure);

                $closure($status);

                return true;
            })
            ->once()
            ->andReturnSelf();
        $importer
            ->shouldReceive('onChange')
            ->withArgs(function (?Closure $closure) use ($status): bool {
                $this->assertNotNull($closure);

                $closure($status);

                return true;
            })
            ->once()
            ->andReturnSelf();
        $importer
            ->shouldReceive('onFinish')
            ->withArgs(function (?Closure $closure): bool {
                $this->assertNotNull($closure);

                $closure();

                return true;
            })
            ->once()
            ->andReturnSelf();
        $importer
            ->shouldReceive('getTotal')
            ->once()
            ->andReturn(1);

        $job->process($configurator, $service, $importer, 1, 1);
    }

    /**
     * @covers ::getDefaultState
     */
    public function testGetDefaultState(): void {
        $job = Mockery::mock(SyncUsersCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        $actual   = $job->getDefaultState(
            $this->app->make(QueueableConfigurator::class)->config($job),
        );
        $expected = new SyncUserState();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getState
     */
    public function testGetState(): void {
        // Mock
        $job = Mockery::mock(SyncUsersCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        // Ok
        $service = Mockery::mock(Service::class);
        $service
            ->shouldReceive('get')
            ->with($job, Mockery::type(Closure::class))
            ->once()
            ->andReturnUsing(static function (object|string $key, Closure $factory = null): ?SyncUserState {
                return $factory([]);
            });

        $actual   = $job->getState($service);
        $expected = new SyncUserState();

        $this->assertEquals($expected, $actual);

        // Throw
        $service = Mockery::mock(Service::class);
        $service
            ->shouldReceive('get')
            ->with($job, Mockery::type(Closure::class))
            ->once()
            ->andThrow(new Exception());

        $this->assertNull($job->getState($service));
    }

    /**
     * @covers ::updateState
     */
    public function testUpdateState(): void {
        $job = Mockery::mock(SyncUsersCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        $service = Mockery::mock(Service::class);
        $service
            ->shouldReceive('set')
            ->with($job, Mockery::type(SyncUserState::class))
            ->once()
            ->andReturnUsing(static function (SyncUsersCronJob $job, SyncUserState $state): SyncUserState {
                return $state;
            });

        $total     = $this->faker->randomNumber();
        $continue  = $this->faker->uuid;
        $processed = $this->faker->randomNumber();
        $state     = new SyncUserState(['processed' => $processed]);
        $status    = new Status($continue, $total, $processed);
        $actual    = $job->updateState($service, $state, $status);
        $expected  = new SyncUserState([
            'total'     => $total,
            'continue'  => $continue,
            'processed' => $processed + $processed,
        ]);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::resetState
     */
    public function testResetState(): void {
        $job = Mockery::mock(SyncUsersCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        $service = Mockery::mock(Service::class);
        $service
            ->shouldReceive('delete')
            ->with($job)
            ->once();

        $job->resetState($service);
    }

    /**
     * @covers ::getProgressCallback
     */
    public function testGetProgressCallback(): void {
        $service = Mockery::mock(Service::class);
        $state   = new SyncUserState([
            'total'     => $this->faker->randomNumber(),
            'processed' => $this->faker->randomNumber(),
        ]);
        $job     = Mockery::mock(SyncUsersCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('getState')
            ->with($service)
            ->once()
            ->andReturn($state);

        $callback = $job->getProgressCallback();
        $progress = $callback($service);
        $expected = new Progress($state->total, $state->processed);

        $this->assertEquals($expected, $progress);
    }

    /**
     * @covers ::getResetProgressCallback
     */
    public function testGetResetProgressCallback(): void {
        $service = Mockery::mock(Service::class);
        $job     = Mockery::mock(SyncUsersCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('resetState')
            ->with($service)
            ->once()
            ->andReturns();

        $callback = $job->getResetProgressCallback();

        $callback($service);
    }
}
