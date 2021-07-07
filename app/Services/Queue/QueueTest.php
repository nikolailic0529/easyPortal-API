<?php declare(strict_types = 1);

namespace App\Services\Queue;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Date;
use Laravel\Horizon\Contracts\JobRepository;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;
use Mockery;
use Tests\TestCase;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\Queue
 */
class QueueTest extends TestCase {
    /**
     * @covers ::getStates
     */
    public function testGetStates(): void {
        // Prepare
        $format   = 'U.u';
        $reserved = Date::now()->format($format);
        $pushedA  = Date::now()->subDay()->format($format);
        $pushedB  = Date::now()->addDay()->format($format);
        $a        = Mockery::mock(Job::class, Stoppable::class);
        $b        = Mockery::mock(Job::class, ShouldBeUnique::class, Stoppable::class);
        $c        = Mockery::mock(Job::class, NamedJob::class, Progressable::class, Stoppable::class);
        $c
            ->shouldReceive('displayName')
            ->atLeast()
            ->once()
            ->andReturn('c');

        $qaa = new QueueJob([
            'id'          => $this->faker->uuid,
            'name'        => $a::class,
            'status'      => QueueJob::STATUS_RESERVED,
            'payload'     => json_encode(['pushedAt' => $pushedA]),
            'reserved_at' => $reserved,
        ]);
        $qab = new QueueJob([
            'id'      => $this->faker->uuid,
            'name'    => $a::class,
            'status'  => QueueJob::STATUS_PENDING,
            'payload' => json_encode(['pushedAt' => $pushedA]),
        ]);
        $qba = new QueueJob([
            'id'      => $this->faker->uuid,
            'name'    => $b::class,
            'status'  => QueueJob::STATUS_COMPLETED,
            'payload' => json_encode(['pushedAt' => $pushedA]),
        ]);
        $qbb = new QueueJob([
            'id'      => $this->faker->uuid,
            'name'    => $b::class,
            'status'  => QueueJob::STATUS_PENDING,
            'payload' => json_encode(['pushedAt' => $pushedA]),
        ]);
        $qc  = new QueueJob([
            'id'      => $this->faker->uuid,
            'name'    => 'c',
            'status'  => QueueJob::STATUS_PENDING,
            'payload' => json_encode(['pushedAt' => $pushedB]),
        ]);

        $repository = Mockery::mock(JobRepository::class);
        $repository
            ->shouldReceive('getPending')
            ->with(0)
            ->once()
            ->andReturn([$qaa, $qab]);
        $repository
            ->shouldReceive('getPending')
            ->with(2)
            ->once()
            ->andReturn([$qba, $qc]);
        $repository
            ->shouldReceive('getPending')
            ->with(4)
            ->once()
            ->andReturn([$qbb]);
        $repository
            ->shouldReceive('getPending')
            ->with(5)
            ->once()
            ->andReturn([]);

        // Queue
        $queue = new Queue($this->app, $this->app->make(Repository::class), $repository);

        $queue->stop($a, $qaa->id);
        $queue->stop($b);
        $queue->stop($c);

        // Test
        $actual   = $queue->getStates([$a, $b, $c]);
        $expected = [
            $a::class => [
                new State(
                    $qab->id,
                    $qab->name,
                    false,
                    false,
                    Date::createFromTimestamp($pushedA),
                    null,
                ),
                new State(
                    $qaa->id,
                    $qaa->name,
                    true,
                    true,
                    Date::createFromTimestamp($pushedA),
                    Date::createFromTimestamp($reserved),
                ),
            ],
            'c'       => [
                new State(
                    $qc->id,
                    $qc->name,
                    false,
                    false,
                    Date::createFromTimestamp((float) $pushedB),
                    null,
                ),
            ],
            $b::class => [
                new State(
                    $qbb->id,
                    $qbb->name,
                    false,
                    true,
                    Date::createFromTimestamp((float) $pushedA),
                    null,
                ),
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getStates
     */
    public function testGetStatesEmptyJobs(): void {
        // Prepare
        $repository = Mockery::mock(JobRepository::class);
        $repository
            ->shouldReceive('getPending')
            ->never();

        // Test
        $actual   = (new Queue($this->app, $this->app->make(Repository::class), $repository))->getStates([]);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getState
     */
    public function testGetState(): void {
        $job   = Mockery::mock(Job::class);
        $state = Mockery::mock(State::class);
        $queue = Mockery::mock(Queue::class);
        $queue->makePartial();
        $queue
            ->shouldReceive('getStates')
            ->with([$job])
            ->once()
            ->andReturn([[$state]]);

        $this->assertEquals([$state], $queue->getState($job));
    }

    /**
     * @covers ::getName
     */
    public function testGetName(): void {
        $job = Mockery::mock(Job::class);
        $job
            ->shouldReceive('displayName')
            ->never();

        $name  = $this->faker->word;
        $named = Mockery::mock(Job::class, NamedJob::class);
        $named
            ->shouldReceive('displayName')
            ->once()
            ->andReturn($name);

        $queue = $this->app->make(Queue::class);

        $this->assertEquals($job::class, $queue->getName($job));
        $this->assertEquals($name, $queue->getName($named));
    }

    /**
     * @covers ::getProgress
     */
    public function testGetProgress(): void {
        $job          = Mockery::mock(Job::class);
        $progress     = new Progress(2, 1);
        $progressable = Mockery::mock(Job::class, Progressable::class);
        $progressable
            ->shouldReceive('getProgressProvider')
            ->once()
            ->andReturn(static function () use ($progress): Progress {
                return $progress;
            });

        $queue = $this->app->make(Queue::class);

        $this->assertNull($queue->getProgress($job));
        $this->assertEquals($progress, $queue->getProgress($progressable));
    }

    /**
     * @covers ::stop
     */
    public function testStop(): void {
        $job       = Mockery::mock(Job::class);
        $stoppable = Mockery::mock(Job::class, Stoppable::class);
        $cache     = Mockery::mock(Repository::class);
        $cache
            ->shouldReceive('set')
            ->once()
            ->andReturn(true);
        $queue = new Queue($this->app, $cache, Mockery::mock(JobRepository::class));

        $this->assertFalse($queue->stop($job));
        $this->assertTrue($queue->stop($stoppable));
    }

    /**
     * @covers ::isStopped
     */
    public function testIsStopped(): void {
        $id    = $this->faker->uuid;
        $jobA  = Mockery::mock(Job::class, Stoppable::class);
        $jobB  = Mockery::mock(Job::class, Stoppable::class);
        $queue = Mockery::mock(Queue::class);
        $queue->makePartial();
        $queue
            ->shouldReceive('getState')
            ->with($jobA)
            ->times(3)
            ->andReturn([
                new State(
                    $id,
                    $jobA::class,
                    true,
                    true,
                    null,
                    null,
                ),
            ]);
        $queue
            ->shouldReceive('getState')
            ->with($jobB)
            ->once()
            ->andReturn([
                // empty
            ]);

        $this->assertTrue($queue->isStopped($jobA));
        $this->assertFalse($queue->isStopped($jobA, $this->faker->uuid));
        $this->assertTrue($queue->isStopped($jobA, $id));
        $this->assertFalse($queue->isStopped($jobB));
    }
}
