<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Services\Queue\Progress;
use App\Services\Search\Eloquent\Searchable;
use App\Services\Search\Service;
use App\Services\Search\Status;
use App\Services\Search\Updater;
use Closure;
use DateTimeInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Jobs\UpdateIndexCronJob
 */
class UpdateIndexCronJobTest extends TestCase {
    /**
     * @covers ::process
     */
    public function testProcess(): void {
        $configurator = $this->app->make(QueueableConfigurator::class);
        $status       = new Status();
        $model        = new class() extends Model {
            use Searchable;

            /**
             * @inheritDoc
             */
            protected static function getSearchProperties(): array {
                return [];
            }
        };

        $service = Mockery::mock(Service::class);
        $updater = Mockery::mock(Updater::class);
        $updater
            ->shouldReceive('onInit')
            ->withArgs(function (?Closure $closure) use ($status): bool {
                $this->assertNotNull($closure);

                $closure($status);

                return true;
            })
            ->once()
            ->andReturnSelf();
        $updater
            ->shouldReceive('onChange')
            ->withArgs(function (?Closure $closure) use ($status): bool {
                $this->assertNotNull($closure);

                $closure(new Collection(), $status);

                return true;
            })
            ->once()
            ->andReturnSelf();
        $updater
            ->shouldReceive('onFinish')
            ->withArgs(function (?Closure $closure): bool {
                $this->assertNotNull($closure);

                $closure();

                return true;
            })
            ->once()
            ->andReturnSelf();
        $updater
            ->shouldReceive('update')
            ->with($model::class, null, null, null)
            ->once()
            ->andReturns();

        $job = Mockery::mock(UpdateIndexCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();
        $job
            ->shouldReceive('updateState')
            ->with($service, Mockery::type(UpdateIndexState::class), $status)
            ->twice()
            ->andReturns();
        $job
            ->shouldReceive('ping')
            ->once();
        $job
            ->shouldReceive('resetState')
            ->with($service)
            ->once()
            ->andReturns();

        $job->process($configurator, $service, $updater, $model::class);
    }

    /**
     * @covers ::getDefaultState
     */
    public function testGetDefaultState(): void {
        $job = Mockery::mock(UpdateIndexCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        $actual   = $job->getDefaultState(
            $this->app->make(QueueableConfigurator::class)->config($job),
        );
        $expected = new UpdateIndexState();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getState
     */
    public function testGetState(): void {
        // Mock
        $job = Mockery::mock(UpdateIndexCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        // Ok
        $service  = Mockery::mock(Service::class);
        $service
            ->shouldReceive('get')
            ->with($job, Mockery::type(Closure::class))
            ->once()
            ->andReturnUsing(static function (object|string $key, Closure $factory = null): ?UpdateIndexState {
                return $factory([]);
            });

        $actual   = $job->getState($service);
        $expected = new UpdateIndexState();

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
        $job = Mockery::mock(UpdateIndexCronJob::class);
        $job->shouldAllowMockingProtectedMethods();
        $job->makePartial();

        $service = Mockery::mock(Service::class);
        $service
            ->shouldReceive('set')
            ->with($job, Mockery::type(UpdateIndexState::class))
            ->once()
            ->andReturnUsing(static function (UpdateIndexCronJob $job, UpdateIndexState $state): UpdateIndexState {
                return $state;
            });

        $from      = Date::now();
        $total     = $this->faker->randomNumber();
        $continue  = $this->faker->uuid;
        $processed = $this->faker->randomNumber();
        $state     = new UpdateIndexState(['processed' => $processed]);
        $status    = new Status($from, $continue, $total, $processed);
        $actual    = $job->updateState($service, $state, $status);
        $expected  = new UpdateIndexState([
            'from'      => $from->format(DateTimeInterface::ATOM),
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
        $job = Mockery::mock(UpdateIndexCronJob::class);
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
        $state   = new UpdateIndexState([
            'total'     => $this->faker->randomNumber(),
            'processed' => $this->faker->randomNumber(),
        ]);
        $job     = Mockery::mock(UpdateIndexCronJob::class);
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
        $job     = Mockery::mock(UpdateIndexCronJob::class);
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
