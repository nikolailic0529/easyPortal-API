<?php declare(strict_types = 1);

namespace App\Services\Queue\Tags;

use App\Services\Queue\Contracts\Stoppable;
use App\Services\Queue\Service;
use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job;
use Mockery;
use Tests\TestCase;

use function microtime;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\Tags\Stop
 */
class StopTest extends TestCase {
    /**
     * @covers ::isMarked
     */
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
            Mockery::mock(Repository::class),
            Mockery::mock(Service::class),
        ]);
        $stop->shouldAllowMockingProtectedMethods();
        $stop->makePartial();
        $stop
            ->shouldReceive('isMarkedById')
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

    /**
     * @covers ::isMarked
     */
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
            Mockery::mock(Repository::class),
            Mockery::mock(Service::class),
        ]);
        $stop->shouldAllowMockingProtectedMethods();
        $stop->makePartial();
        $stop
            ->shouldReceive('isMarkedById')
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

    /**
     * @covers ::isMarkedById
     */
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

        $service = Mockery::mock(Service::class);
        $stop    = new class(Mockery::mock(Repository::class), $service) extends Stop {
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

    /**
     * @covers ::isMarkedByMarker
     */
    public function testIsMarkedByMarker(): void {
        $time      = microtime(true);
        $stoppable = Mockery::mock(Stoppable::class);
        $service   = Mockery::mock(Service::class);
        $stop      = new class(Mockery::mock(Repository::class), $service) extends Stop {
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

    /**
     * @covers ::isMarked
     */
    public function testIsMarkedByMarkerOutdated(): void {
        $time      = microtime(true);
        $stoppable = Mockery::mock(Stoppable::class);
        $service   = Mockery::mock(Service::class);
        $stop      = new class(Mockery::mock(Repository::class), $service) extends Stop {
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

    /**
     * @covers ::isMarkedByQueueRestart
     */
    public function testIsMarkedQueueRestart(): void {
        $time    = microtime(true);
        $service = Mockery::mock(Service::class);
        $cache   = Mockery::mock(Repository::class);
        $stop    = new class($cache, $service) extends Stop {
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

    /**
     * @covers ::isMarkedByQueueRestart
     */
    public function testIsMarkedByQueueRestartOutdated(): void {
        $time    = microtime(true);
        $service = Mockery::mock(Service::class);
        $cache   = Mockery::mock(Repository::class);
        $stop    = new class($cache, $service) extends Stop {
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

    /**
     * @covers ::mark
     */
    public function testMark(): void {
        $id        = $this->faker->uuid();
        $stoppable = Mockery::mock(Stoppable::class);
        $service   = Mockery::mock(Service::class);
        $stop      = new Stop(Mockery::mock(Repository::class), $service);

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
