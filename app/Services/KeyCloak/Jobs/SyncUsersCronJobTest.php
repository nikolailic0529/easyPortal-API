<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use App\Services\KeyCloak\Importer\Status;
use App\Services\Queue\Progress;
use Closure;
use Exception;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Jobs\SyncUsersCronJob
 */
class SyncUsersCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(SyncUsersCronJob::class);
    }

    /**
     * @covers ::getDefaultState
     */
    public function testGetDefaultState(): void {
        $job = Mockery::mock(SyncUsersCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        $actual   = $job->getDefaultState();
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
        $service  = Mockery::mock(Service::class);
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
