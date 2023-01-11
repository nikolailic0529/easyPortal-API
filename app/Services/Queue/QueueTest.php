<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Logger\Models\Enums\Action;
use App\Services\Logger\Models\Enums\Category;
use App\Services\Logger\Models\Enums\Status;
use App\Services\Logger\Models\Log;
use App\Services\Queue\Contracts\NamedJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\Contracts\Stoppable;
use App\Services\Queue\Tags\Stop;
use App\Utils\Processor\State;
use Generator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Laravel\Horizon\Contracts\JobRepository;
use LastDragon_ru\LaraASP\Queue\Queueables\Job as BaseJob;
use Mockery;
use Tests\TestCase;

use function iterator_to_array;
use function json_encode;

/**
 * @internal
 * @covers \App\Services\Queue\Queue
 */
class QueueTest extends TestCase {
    public function testGetStates(): void {
        // Prepare
        $aa = new JobState('a', '1', false, false, Date::now()->subDay(), Date::now());
        $ab = new JobState('a', '2', false, false, Date::now()->addDay(), Date::now()->addDay());
        $ca = new JobState('c', '3', false, false, Date::now()->subDay(), Date::now());
        $ba = new JobState('d', '4', false, false, Date::now()->subDay(), Date::now());

        // Mock
        $queue = Mockery::mock(Queue::class);
        $queue->shouldAllowMockingProtectedMethods();
        $queue->makePartial();
        $queue
            ->shouldReceive('getStatesFromHorizon')
            ->once()
            ->andReturnUsing(static function () use ($aa, $ca): Generator {
                yield from [$aa, $ca];
            });
        $queue
            ->shouldReceive('getStatesFromLogs')
            ->once()
            ->andReturnUsing(static function () use ($ab, $ba): Generator {
                yield from [$ab, $ba];
            });

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

        self::assertEquals($expected, $actual);
    }

    public function testGetStatesEmptyJobs(): void {
        // Prepare
        $repository = Mockery::mock(JobRepository::class);
        $repository
            ->shouldReceive('getPending')
            ->never();

        // Test
        $stopTag  = $this->app->make(Stop::class);
        $actual   = (new Queue($stopTag, $repository))->getStates([]);
        $expected = [];

        self::assertEquals($expected, $actual);
    }

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
            'id'          => $this->faker->uuid(),
            'name'        => $a::class,
            'status'      => QueueJob::STATUS_RESERVED,
            'payload'     => json_encode(['pushedAt' => $pushedA]),
            'reserved_at' => $reserved,
        ]);
        $qab = new QueueJob([
            'id'      => $this->faker->uuid(),
            'name'    => $a::class,
            'status'  => QueueJob::STATUS_PENDING,
            'payload' => json_encode(['pushedAt' => $pushedA]),
        ]);
        $qba = new QueueJob([
            'id'      => $this->faker->uuid(),
            'name'    => $b::class,
            'status'  => QueueJob::STATUS_COMPLETED,
            'payload' => json_encode(['pushedAt' => $pushedA]),
        ]);
        $qbb = new QueueJob([
            'id'      => $this->faker->uuid(),
            'name'    => $b::class,
            'status'  => QueueJob::STATUS_PENDING,
            'payload' => json_encode(['pushedAt' => $pushedA]),
        ]);
        $qc  = new QueueJob([
            'id'      => $this->faker->uuid(),
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

        $stop = Mockery::mock(Stop::class);
        $stop
            ->shouldReceive('isMarked')
            ->with($a)
            ->twice()
            ->andReturn(true);
        $stop
            ->shouldReceive('isMarked')
            ->with($b)
            ->once()
            ->andReturn(true);
        $stop
            ->shouldReceive('isMarked')
            ->with($c)
            ->once()
            ->andReturn(false);

        // Queue
        $queue = new class($stop, $repository) extends Queue {
            public function getStatesFromHorizon(Collection $jobs): Generator {
                return parent::getStatesFromHorizon($jobs);
            }
        };

        // Test
        $jobs     = (new Collection([$a, $b, $c]))
            ->keyBy(static function (BaseJob $job): string {
                return $job instanceof NamedJob ? $job->displayName() : $job::class;
            });
        $actual   = iterator_to_array($queue->getStatesFromHorizon($jobs));
        $expected = [
            new JobState(
                $qaa->name,
                $qaa->id,
                true,
                true,
                Date::createFromTimestamp($pushedA),
                Date::createFromTimestamp($reserved),
            ),
            new JobState(
                $qab->name,
                $qab->id,
                false,
                true,
                Date::createFromTimestamp($pushedA),
                null,
            ),
            new JobState(
                $qc->name,
                $qc->id,
                false,
                false,
                Date::createFromTimestamp((float) $pushedB),
                null,
            ),
            new JobState(
                $qbb->name,
                $qbb->id,
                false,
                true,
                Date::createFromTimestamp((float) $pushedA),
                null,
            ),
        ];

        self::assertEquals($expected, $actual);
    }

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
            'created_at'  => Date::now()->subDay(),
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
        $lc  = Log::factory()->create([
            'object_type' => 'c',
            'object_id'   => '918d0938-bffe-4ae6-8a3e-b62dcf4df2ef',
            'category'    => Category::queue(),
            'action'      => Action::queueJobRun(),
            'status'      => Status::active(),
            'context'     => 'Should be returned',
        ]);

        Log::factory()->create([
            'object_type' => $b::class,
            'object_id'   => 'd1f851f0-2453-4d1b-ae9b-3a270459ac86',
            'category'    => Category::queue(),
            'action'      => Action::queueJobRun(),
            'status'      => Status::success(),
            'context'     => 'Should be ignored because status != active',
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
        $stopTag    = Mockery::mock(Stop::class);
        $queue      = new class($stopTag, $repository) extends Queue {
            public function getStatesFromLogs(Collection $jobs): Generator {
                return parent::getStatesFromLogs($jobs);
            }
        };

        $stopTag
            ->shouldReceive('isMarked')
            ->with($a)
            ->once()
            ->andReturn(true);
        $stopTag
            ->shouldReceive('isMarked')
            ->with($c)
            ->once()
            ->andReturn(true);

        // Test
        $jobs     = (new Collection([$a, $b, $c]))
            ->keyBy(static function (BaseJob $job): string {
                return $job instanceof NamedJob ? $job->displayName() : $job::class;
            });
        $actual   = iterator_to_array($queue->getStatesFromLogs($jobs));
        $expected = [
            new JobState(
                $laa->object_type,
                $laa->object_id,
                true,
                true,
                $lab->created_at,
                $laa->created_at,
            ),
            new JobState(
                $lc->object_type,
                $lc->object_id,
                true,
                true,
                null,
                $lc->created_at,
            ),
        ];

        self::assertEquals($expected, $actual);
    }

    public function testGetStatesFromLogsNoExpired(): void {
        // Prepare
        $expire = 3600;
        $a      = Mockery::mock(BaseJob::class, Stoppable::class);
        $b      = Mockery::mock(BaseJob::class, ShouldBeUnique::class, Stoppable::class);
        $la     = Log::factory()->create([
            'object_type' => $a::class,
            'object_id'   => '48d53ba5-9d6b-4c6d-9a0b-d832143bb385',
            'category'    => Category::queue(),
            'action'      => Action::queueJobRun(),
            'status'      => Status::active(),
            'context'     => 'Should be returned',
            'created_at'  => Date::now()->subDay(),
            'updated_at'  => Date::now()->subSeconds($expire - 60),
        ]);

        Log::factory()->create([
            'object_type' => $b::class,
            'object_id'   => '48d53ba5-9d6b-4c6d-9a0b-d832143bb385',
            'category'    => Category::queue(),
            'action'      => Action::queueJobRun(),
            'status'      => Status::active(),
            'context'     => 'Should not be returned (expired)',
            'created_at'  => Date::now()->subDay(),
            'updated_at'  => Date::now()->subSeconds($expire),
        ]);

        // Update settings
        $config     = $this->app->make(Repository::class);
        $connection = $config->get('queue.default');

        $this->setSettings([
            "queue.connections.{$connection}.retry_after" => $expire,
        ]);

        // Queue
        $repository = Mockery::mock(JobRepository::class);
        $stopTag    = Mockery::mock(Stop::class);
        $queue      = new class($stopTag, $repository) extends Queue {
            public function getStatesFromLogs(Collection $jobs): Generator {
                return parent::getStatesFromLogs($jobs);
            }
        };

        $stopTag
            ->shouldReceive('isMarked')
            ->with($a)
            ->once()
            ->andReturn(false);

        // Test
        $jobs     = (new Collection([$a, $b]))
            ->keyBy(static function (BaseJob $job): string {
                return $job instanceof NamedJob ? $job->displayName() : $job::class;
            });
        $actual   = iterator_to_array($queue->getStatesFromLogs($jobs));
        $expected = [
            new JobState(
                $la->object_type,
                $la->object_id,
                true,
                false,
                null,
                $la->created_at,
            ),
        ];

        self::assertEquals($expected, $actual);
    }

    public function testGetState(): void {
        $job   = Mockery::mock(BaseJob::class);
        $state = Mockery::mock(JobState::class);
        $queue = Mockery::mock(Queue::class);
        $queue->makePartial();
        $queue
            ->shouldReceive('getStates')
            ->with([$job])
            ->once()
            ->andReturn([[$state]]);

        self::assertEquals([$state], $queue->getState($job));
    }

    public function testGetName(): void {
        $job = Mockery::mock(BaseJob::class);
        $job
            ->shouldReceive('displayName')
            ->never();

        $name  = $this->faker->word();
        $named = Mockery::mock(BaseJob::class, NamedJob::class);
        $named
            ->shouldReceive('displayName')
            ->once()
            ->andReturn($name);

        $queue = $this->app->make(Queue::class);

        self::assertEquals($job::class, $queue->getName($job));
        self::assertEquals($name, $queue->getName($named));
    }

    public function testGetProgress(): void {
        $job          = Mockery::mock(BaseJob::class);
        $progress     = new Progress(new State(['total' => 2, 'processed' => 1]), null, null);
        $progressable = Mockery::mock(BaseJob::class, Progressable::class);
        $progressable
            ->shouldReceive('getProgressCallback')
            ->once()
            ->andReturn(static function () use ($progress): Progress {
                return $progress;
            });

        $queue = $this->app->make(Queue::class);

        self::assertNull($queue->getProgress($job));
        self::assertEquals($progress, $queue->getProgress($progressable));
    }

    public function testStop(): void {
        $id        = $this->faker->uuid();
        $job       = Mockery::mock(BaseJob::class);
        $stoppable = Mockery::mock(BaseJob::class, Stoppable::class);
        $stopTag   = Mockery::mock(Stop::class);
        $stopTag
            ->shouldReceive('mark')
            ->with($stoppable, null)
            ->once()
            ->andReturn(true);
        $stopTag
            ->shouldReceive('mark')
            ->with($stoppable, $id)
            ->once()
            ->andReturn(true);
        $queue = Mockery::mock(Queue::class, [$stopTag, Mockery::mock(JobRepository::class)]);
        $queue->makePartial();
        $queue
            ->shouldReceive('getState')
            ->never();

        self::assertFalse($queue->stop($job));
        self::assertTrue($queue->stop($stoppable));
        self::assertTrue($queue->stop($stoppable, $id));
    }

    public function testIsStopped(): void {
        $jobA    = Mockery::mock(BaseJob::class, Stoppable::class);
        $jobB    = Mockery::mock(BaseJob::class);
        $stopTag = Mockery::mock(Stop::class);
        $stopTag
            ->shouldReceive('isMarked')
            ->with($jobA)
            ->once()
            ->andReturn(true);

        $queue = new Queue($stopTag, Mockery::mock(JobRepository::class));

        self::assertTrue($queue->isStopped($jobA));
        self::assertFalse($queue->isStopped($jobB));
    }
}
