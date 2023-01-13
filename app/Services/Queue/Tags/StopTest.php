<?php declare(strict_types = 1);

namespace App\Services\Queue\Tags;

use App\Services\Queue\Contracts\Stoppable;
use App\Services\Queue\Service;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Mockery;
use Tests\TestCase;

use function microtime;

/**
 * @internal
 * @covers \App\Services\Queue\Tags\Stop
 */
class StopTest extends TestCase {
    public function testIsMarked(): void {
        $time = microtime(true);
        $job  = Mockery::mock(Job::class, JobContract::class, Stoppable::class);
        $job
            ->shouldReceive('payload')
            ->once()
            ->andReturn([
                'pushedAt' => $time,
            ]);

        $stoppable = Mockery::mock(Stoppable::class);
        $stoppable
            ->shouldReceive('getJob')
            ->once()
            ->andReturn($job);

        $stop = Mockery::mock(Stop::class, [
            Mockery::mock(Application::class),
            Mockery::mock(Repository::class),
            Mockery::mock(Service::class),
            Mockery::mock(MasterSupervisorRepository::class),
        ]);
        $stop->shouldAllowMockingProtectedMethods();
        $stop->makePartial();
        $stop
            ->shouldReceive('isMarkedById')
            ->once()
            ->andReturn(false);
        $stop
            ->shouldReceive('isMarkedBySupervisor')
            ->once()
            ->andReturn(false);
        $stop
            ->shouldReceive('isMarkedByMarker')
            ->once()
            ->andReturn(false);
        $stop
            ->shouldReceive('isMarkedByQueueRestart')
            ->once()
            ->andReturn(true);

        self::assertTrue($stop->isMarked($stoppable));
    }

    public function testIsMarkedUnknownDispatched(): void {
        $job = Mockery::mock(Job::class, JobContract::class, Stoppable::class);
        $job
            ->shouldReceive('payload')
            ->once()
            ->andReturn([
                // empty
            ]);

        $stoppable = Mockery::mock(Stoppable::class);
        $stoppable
            ->shouldReceive('getJob')
            ->once()
            ->andReturn($job);

        $stop = Mockery::mock(Stop::class, [
            Mockery::mock(Application::class),
            Mockery::mock(Repository::class),
            Mockery::mock(Service::class),
            Mockery::mock(MasterSupervisorRepository::class),
        ]);
        $stop->shouldAllowMockingProtectedMethods();
        $stop->makePartial();
        $stop
            ->shouldReceive('isMarkedById')
            ->once()
            ->andReturn(false);
        $stop
            ->shouldReceive('isMarkedBySupervisor')
            ->once()
            ->andReturn(false);
        $stop
            ->shouldReceive('isMarkedByMarker')
            ->never();
        $stop
            ->shouldReceive('isMarkedByQueueRestart')
            ->never();

        self::assertFalse($stop->isMarked($stoppable));
    }

    public function testIsMarkedById(): void {
        $id  = $this->faker->uuid();
        $job = Mockery::mock(JobContract::class);
        $job
            ->shouldReceive('getJobId')
            ->once()
            ->andReturn($id);

        $stoppable = Mockery::mock(Stoppable::class);
        $stoppable
            ->shouldReceive('getJob')
            ->once()
            ->andReturn($job);

        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new class($application, $cache, $service, $repository) extends Stop {
            public function isMarkedById(Stoppable $stoppable): bool {
                return parent::isMarkedById($stoppable);
            }
        };

        $service
            ->shouldReceive('has')
            ->with([$stop, $stoppable, $id])
            ->once()
            ->andReturn(true);

        self::assertTrue($stop->isMarkedById($stoppable));
    }

    public function testIsMarkedByIdUnknownJob(): void {
        $stoppable = Mockery::mock(Stoppable::class);
        $stoppable
            ->shouldReceive('getJob')
            ->once()
            ->andReturn(null);

        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new class($application, $cache, $service, $repository) extends Stop {
            public function isMarkedById(Stoppable $stoppable): bool {
                return parent::isMarkedById($stoppable);
            }
        };

        $service
            ->shouldReceive('has')
            ->never();

        self::assertFalse($stop->isMarkedById($stoppable));
    }

    public function testIsMarkedByMarker(): void {
        $time        = microtime(true);
        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $stoppable   = Mockery::mock(Stoppable::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new class($application, $cache, $service, $repository) extends Stop {
            public function isMarkedByMarker(Stoppable $stoppable, float $dispatched): bool {
                return parent::isMarkedByMarker($stoppable, $dispatched);
            }
        };

        $service
            ->shouldReceive('get')
            ->with([$stop, $stoppable], Mockery::type(Closure::class))
            ->once()
            ->andReturn($time);

        self::assertTrue($stop->isMarkedByMarker($stoppable, $time - 100));
    }

    public function testIsMarkedByMarkerOutdated(): void {
        $time        = microtime(true);
        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $stoppable   = Mockery::mock(Stoppable::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new class($application, $cache, $service, $repository) extends Stop {
            public function isMarkedByMarker(Stoppable $stoppable, float $dispatched): bool {
                return parent::isMarkedByMarker($stoppable, $dispatched);
            }
        };

        $service
            ->shouldReceive('get')
            ->with([$stop, $stoppable], Mockery::type(Closure::class))
            ->once()
            ->andReturn($time);

        self::assertFalse($stop->isMarkedByMarker($stoppable, $time + 100));
    }

    public function testIsMarkedQueueRestart(): void {
        $time        = microtime(true);
        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new class($application, $cache, $service, $repository) extends Stop {
            public function isMarkedByQueueRestart(float $dispatched): bool {
                return parent::isMarkedByQueueRestart($dispatched);
            }
        };

        $cache
            ->shouldReceive('get')
            ->with('illuminate:queue:restart')
            ->once()
            ->andReturn($time);

        self::assertTrue($stop->isMarkedByQueueRestart($time - 100));
    }

    public function testIsMarkedByQueueRestartOutdated(): void {
        $time        = microtime(true);
        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new class($application, $cache, $service, $repository) extends Stop {
            public function isMarkedByQueueRestart(float $dispatched): bool {
                return parent::isMarkedByQueueRestart($dispatched);
            }
        };

        $cache
            ->shouldReceive('get')
            ->with('illuminate:queue:restart')
            ->once()
            ->andReturn($time);

        self::assertFalse($stop->isMarkedByQueueRestart($time + 100));
    }

    public function testIsMarkedBySignal(): void {
        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new class($application, $cache, $service, $repository) extends Stop {
            public function isMarkedBySupervisor(): bool {
                return parent::isMarkedBySupervisor();
            }
        };

        $application
            ->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(false);

        $repository
            ->shouldReceive('all')
            ->once()
            ->andReturn([
                // empty
            ]);

        self::assertTrue($stop->isMarkedBySupervisor());
    }

    public function testIsMarkedBySignalUnitTests(): void {
        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new class($application, $cache, $service, $repository) extends Stop {
            public function isMarkedBySupervisor(): bool {
                return parent::isMarkedBySupervisor();
            }
        };

        $application
            ->shouldReceive('runningUnitTests')
            ->once()
            ->andReturn(true);

        $repository
            ->shouldReceive('all')
            ->never();

        self::assertFalse($stop->isMarkedBySupervisor());
    }

    public function testMark(): void {
        $id          = $this->faker->uuid();
        $application = Mockery::mock(Application::class);
        $repository  = Mockery::mock(MasterSupervisorRepository::class);
        $stoppable   = Mockery::mock(Stoppable::class);
        $service     = Mockery::mock(Service::class);
        $cache       = Mockery::mock(Repository::class);
        $stop        = new Stop($application, $cache, $service, $repository);

        $service
            ->shouldReceive('set')
            ->with([$stop, $stoppable, $id], Mockery::type('float'))
            ->once()
            ->andReturn(true);
        $service
            ->shouldReceive('set')
            ->with([$stop, $stoppable], Mockery::type('float'))
            ->once()
            ->andReturn(true);

        self::assertTrue($stop->mark($stoppable, $id));
        self::assertTrue($stop->mark($stoppable, null));
    }
}
