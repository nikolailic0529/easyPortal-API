<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Logger\Models\Enums\Action;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Horizon\Contracts\JobRepository;
use LastDragon_ru\LaraASP\Queue\Queueables\Job as BaseJob;
use Mockery;
use Tests\TestCase;

use function array_fill_keys;
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
        $aa = new State('1', 'a', false, false, Date::now()->subDay(), Date::now());
        $ab = new State('2', 'a', false, false, Date::now()->addDay(), Date::now()->addDay());
        $ca = new State('3', 'c', false, false, Date::now()->subDay(), Date::now());
        $ba = new State('4', 'd', false, false, Date::now()->subDay(), Date::now());

        // Mock
        $queue = Mockery::mock(Queue::class);
        $queue->shouldAllowMockingProtectedMethods();
        $queue->makePartial();
        $queue
            ->shouldReceive('getStatesFromHorizon')
            ->once()
            ->andReturn([
                $aa->name => [
                    $aa->id => $aa,
                ],
                $ca->name => [
                    $ca->id => $ca,
                ],
            ]);
        $queue
            ->shouldReceive('getStatesFromLogs')
            ->once()
            ->andReturn([
                $ab->name => [
                    $ab->id => $ab,
                ],
                $ba->name => [
                    $ba->id => $ba,
                ],
            ]);

        // Test
        $actual   = $queue->getStates([
            new class() extends Job {
                /** @noinspection PhpMissingParentConstructorInspection */
                public function __construct() {
                    // empty
                }

                public function displayName(): string {
                    return 'a';
                }
            },
            new class() extends Job {
                /** @noinspection PhpMissingParentConstructorInspection */
                public function __construct() {
                    // empty
                }

                public function displayName(): string {
                    return 'b';
                }
            },
            new class() extends Job {
                /** @noinspection PhpMissingParentConstructorInspection */
                public function __construct() {
                    // empty
                }

                public function displayName(): string {
                    return 'c';
                }
            },
        ]);
        $expected = [
            $aa->name => [
                $ab,
                $aa,
            ],
            $ca->name => [
                $ca,
            ],
            $ba->name => [
                $ba,
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
     * @covers ::getStatesFromHorizon
     */
    public function testGetStatesFromHorizon(): void {
        // Prepare
        $format   = 'U.u';
        $reserved = Date::now()->format($format);
        $pushedA  = Date::now()->subDay()->format($format);
        $pushedB  = Date::now()->addDay()->format($format);
        $a        = Mockery::mock(BaseJob::class, Stoppable::class);
        $b        = Mockery::mock(BaseJob::class, ShouldBeUnique::class, Stoppable::class);
        $c        = Mockery::mock(BaseJob::class, NamedJob::class, Progressable::class, Stoppable::class);
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
        $queue = new class($this->app, $this->app->make(Repository::class), $repository) extends Queue {
            /**
             * @inheritDoc
             */
            public function getStatesFromHorizon(Collection $jobs, array $states): array {
                return parent::getStatesFromHorizon($jobs, $states);
            }
        };

        $queue->stop($a, $qaa->id);
        $queue->stop($b);
        $queue->stop($c);

        // Test
        $jobs     = (new Collection([$a, $b, $c]))
            ->keyBy(static function (BaseJob $job): string {
                return $job instanceof NamedJob ? $job->displayName() : $job::class;
            });
        $states   = array_fill_keys($jobs->keys()->all(), []);
        $actual   = $queue->getStatesFromHorizon($jobs, $states);
        $expected = [
            $a::class => [
                $qab->id => new State(
                    $qab->id,
                    $qab->name,
                    false,
                    false,
                    Date::createFromTimestamp($pushedA),
                    null,
                ),
                $qaa->id => new State(
                    $qaa->id,
                    $qaa->name,
                    true,
                    true,
                    Date::createFromTimestamp($pushedA),
                    Date::createFromTimestamp($reserved),
                ),
            ],
            'c'       => [
                $qc->id => new State(
                    $qc->id,
                    $qc->name,
                    false,
                    false,
                    Date::createFromTimestamp((float) $pushedB),
                    null,
                ),
            ],
            $b::class => [
                $qbb->id => new State(
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
     * @covers ::getStatesFromLogs
     */
    public function testGetStatesFromLogs(): void {
        // Prepare
        $a = Mockery::mock(BaseJob::class, Stoppable::class);
        $b = Mockery::mock(BaseJob::class, ShouldBeUnique::class, Stoppable::class);
        $c = Mockery::mock(BaseJob::class, NamedJob::class, Progressable::class, Stoppable::class);
        $c
            ->shouldReceive('displayName')
            ->atLeast()
            ->once()
            ->andReturn('c');

        $laa = Log::factory()->create([
            'object_type' => $a::class,
            'object_id'   => '48d53ba5-9d6b-4c6d-9a0b-d832143bb385',
            'category'    => Category::queue(),
            'action'      => Action::queueJobRun(),
            'status'      => Status::active(),
            'context'     => 'Should be returned',
        ]);
        $lab = Log::factory()->create([
            'object_type' => $a::class,
            'object_id'   => '48d53ba5-9d6b-4c6d-9a0b-d832143bb385',
            'category'    => Category::queue(),
            'action'      => Action::queueJobDispatched(),
            'status'      => Status::success(),
            'context'     => 'Dispatched time for $a',
            'created_at'  => Date::now()->subDay(),
        ]);
        $lb  = Log::factory()->create([
            'object_type' => $b::class,
            'object_id'   => 'd1f851f0-2453-4d1b-ae9b-3a270459ac86',
            'category'    => Category::queue(),
            'action'      => Action::queueJobRun(),
            'status'      => Status::success(),
            'context'     => 'Should be ignored because status != active',
        ]);
        $lc  = Log::factory()->create([
            'object_type' => 'c',
            'object_id'   => '918d0938-bffe-4ae6-8a3e-b62dcf4df2ef',
            'category'    => Category::queue(),
            'action'      => Action::queueJobRun(),
            'status'      => Status::active(),
            'context'     => 'Should be returned',
        ]);

        Log::factory()->create([
            'object_type' => 'd',
            'object_id'   => '918d0938-bffe-4ae6-8a3e-b62dcf4df2ef',
            'category'    => Category::queue(),
            'action'      => Action::queueJobRun(),
            'status'      => Status::active(),
            'context'     => 'Should be ignored because not in $jobs',
        ]);

        // Queue
        $repository = Mockery::mock(JobRepository::class);
        $cache      = $this->app->make(Repository::class);
        $queue      = new class($this->app, $cache, $repository) extends Queue {
            /**
             * @inheritDoc
             */
            public function getStatesFromLogs(Collection $jobs, array $states): array {
                return parent::getStatesFromLogs($jobs, $states);
            }
        };

        $queue->stop($a, '48d53ba5-9d6b-4c6d-9a0b-d832143bb385'); // Should be stopped (dispatch time is known)
        $queue->stop($c);                                         // Should be not (no dispatch time)

        // Test
        $jobs     = (new Collection([$a, $b, $c]))
            ->keyBy(static function (BaseJob $job): string {
                return $job instanceof NamedJob ? $job->displayName() : $job::class;
            });
        $default  = [
            $laa->object_type => [
                'f534087e-7c9a-424f-9f37-22c527ef35af' => new State('a', 'a', false, false, null, null),
            ],
        ];
        $states   = $default + array_fill_keys($jobs->keys()->all(), []);
        $actual   = $queue->getStatesFromLogs($jobs, $states);
        $expected = [
            $laa->object_type => [
                'f534087e-7c9a-424f-9f37-22c527ef35af' => new State('a', 'a', false, false, null, null),
                $laa->object_id                        => new State(
                    $laa->object_id,
                    $laa->object_type,
                    true,
                    true,
                    $lab->created_at,
                    $laa->created_at,
                ),
            ],
            $lb->object_type  => [
                // empty
            ],
            $lc->object_type  => [
                $lc->object_id => new State(
                    $lc->object_id,
                    $lc->object_type,
                    true,
                    false,
                    null,
                    $lc->created_at,
                ),
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getState
     */
    public function testGetState(): void {
        $job   = Mockery::mock(BaseJob::class);
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
        $job = Mockery::mock(BaseJob::class);
        $job
            ->shouldReceive('displayName')
            ->never();

        $name  = $this->faker->word;
        $named = Mockery::mock(BaseJob::class, NamedJob::class);
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
        $job          = Mockery::mock(BaseJob::class);
        $progress     = new Progress(2, 1);
        $progressable = Mockery::mock(BaseJob::class, Progressable::class);
        $progressable
            ->shouldReceive('getProgressCallback')
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
        $job       = Mockery::mock(BaseJob::class);
        $stoppable = Mockery::mock(BaseJob::class, Stoppable::class);
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
        $jobA  = Mockery::mock(BaseJob::class, Stoppable::class);
        $jobB  = Mockery::mock(BaseJob::class, Stoppable::class);
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
