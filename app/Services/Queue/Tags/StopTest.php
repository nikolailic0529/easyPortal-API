<?php declare(strict_types = 1);

namespace App\Services\Queue\Tags;

use App\Services\Queue\Contracts\Stoppable;
use App\Services\Queue\Service;
use Closure;
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
    public function testIsMarkedExplicit(): void {
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
        $stop    = new Stop($service);

        $service
            ->shouldReceive('has')
            ->with([$stop, $stoppable, $id])
            ->once()
            ->andReturn(true);

        self::assertTrue($stop->isMarked($stoppable));
    }

    /**
     * @covers ::isMarked
     */
    public function testIsMarkedMarker(): void {
        $id   = $this->faker->uuid();
        $time = microtime(true);
        $job  = Mockery::mock(Job::class, JobContract::class, Stoppable::class);
        $job
            ->shouldReceive('getJobId')
            ->once()
            ->andReturn($id);
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

        $service = Mockery::mock(Service::class);
        $stop    = new Stop($service);

        $service
            ->shouldReceive('has')
            ->with([$stop, $stoppable, $id])
            ->once()
            ->andReturn(false);
        $service
            ->shouldReceive('get')
            ->with([$stop, $stoppable], Mockery::type(Closure::class))
            ->once()
            ->andReturn($time + 100);

        self::assertTrue($stop->isMarked($stoppable));
    }

    /**
     * @covers ::isMarked
     */
    public function testIsMarkedOutdated(): void {
        $id   = $this->faker->uuid();
        $time = microtime(true);
        $job  = Mockery::mock(Job::class, JobContract::class, Stoppable::class);
        $job
            ->shouldReceive('getJobId')
            ->once()
            ->andReturn($id);
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

        $service = Mockery::mock(Service::class);
        $stop    = new Stop($service);

        $service
            ->shouldReceive('has')
            ->with([$stop, $stoppable, $id])
            ->once()
            ->andReturn(false);
        $service
            ->shouldReceive('get')
            ->with([$stop, $stoppable], Mockery::type(Closure::class))
            ->once()
            ->andReturn($time - 100);

        self::assertFalse($stop->isMarked($stoppable));
    }

    /**
     * @covers ::isMarked
     */
    public function testIsMarkedUnknownJob(): void {
        $id  = $this->faker->uuid();
        $job = Mockery::mock(JobContract::class, Stoppable::class);
        $job
            ->shouldReceive('getJobId')
            ->once()
            ->andReturn($id);
        $job
            ->shouldReceive('payload')
            ->never();

        $stoppable = Mockery::mock(Stoppable::class);
        $stoppable
            ->shouldReceive('getJob')
            ->once()
            ->andReturn($job);

        $service = Mockery::mock(Service::class);
        $stop    = new Stop($service);

        $service
            ->shouldReceive('has')
            ->with([$stop, $stoppable, $id])
            ->once()
            ->andReturn(false);
        $service
            ->shouldReceive('get')
            ->never();

        self::assertFalse($stop->isMarked($stoppable));
    }

    /**
     * @covers ::isMarked
     */
    public function testIsMarkedUnknownDispatched(): void {
        $id  = $this->faker->uuid();
        $job = Mockery::mock(Job::class, JobContract::class, Stoppable::class);
        $job
            ->shouldReceive('getJobId')
            ->once()
            ->andReturn($id);
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

        $service = Mockery::mock(Service::class);
        $stop    = new Stop($service);

        $service
            ->shouldReceive('has')
            ->with([$stop, $stoppable, $id])
            ->once()
            ->andReturn(false);
        $service
            ->shouldReceive('get')
            ->never();

        self::assertFalse($stop->isMarked($stoppable));
    }

    /**
     * @covers ::mark
     */
    public function testMark(): void {
        $id        = $this->faker->uuid();
        $stoppable = Mockery::mock(Stoppable::class);
        $service   = Mockery::mock(Service::class);
        $stop      = new Stop($service);

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
