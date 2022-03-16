<?php declare(strict_types = 1);

namespace App\Services\Queue;

use App\Services\Queue\Events\JobStopped as JobStoppedEvent;
use App\Services\Queue\Exceptions\JobStopped;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\Job as QueueJob;
use Illuminate\Contracts\Redis\Connection;
use Illuminate\Queue\Jobs\RedisJob;
use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\Pinger
 */
class PingerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::ping
     *
     * @dataProvider dataProviderJobClass
     *
     * @param class-string<CronJob|Job> $class
     */
    public function testPing(string $class): void {
        $pinger = Mockery::mock(Pinger::class);
        $pinger->shouldAllowMockingProtectedMethods();
        $pinger->makePartial();
        $pinger
            ->shouldReceive('stop')
            ->once();
        $pinger
            ->shouldReceive('prolong')
            ->once();

        $pinger->ping(Mockery::mock($class));
    }

    /**
     * @covers ::stop
     *
     * @dataProvider dataProviderJobClass
     *
     * @param class-string<CronJob|Job> $class
     */
    public function testStop(string $class): void {
        // Fake
        Event::fake(JobStoppedEvent::class);

        // Mocks
        $uuid     = $this->faker->uuid;
        $queueJob = Mockery::mock(QueueJob::class);
        $queueJob
            ->shouldReceive('getJobId')
            ->once()
            ->andReturn($uuid);

        $job = Mockery::mock($class);
        $job
            ->shouldReceive('getJob')
            ->twice()
            ->andReturn($queueJob);

        $service = Mockery::mock(Queue::class);
        $service
            ->shouldReceive('isStopped')
            ->with($job, $uuid)
            ->once()
            ->andReturn(true);

        $pinger = Mockery::mock(Pinger::class, [$this->app->make(Dispatcher::class), $service]);
        $pinger->makePartial();

        // Test
        $this->expectException(JobStopped::class);

        try {
            $pinger->ping($job);
        } catch (JobStopped $exception) {
            Event::assertDispatched(
                JobStoppedEvent::class,
                static function (JobStoppedEvent $event) use ($queueJob): bool {
                    return $event->getJob() === $queueJob;
                },
            );

            throw $exception;
        }
    }

    /**
     * @covers ::prolong
     *
     * @dataProvider dataProviderJobClass
     *
     * @param class-string<CronJob|Job> $class
     */
    public function testProlong(string $class): void {
        $now       = Date::now()->timestamp;
        $uuid      = $this->faker->uuid;
        $queueName = $this->faker->word;

        $connection = Mockery::mock(Connection::class);
        $connection
            ->shouldReceive('zadd')
            ->with("{$queueName}:reserved", $now, $uuid)
            ->once();

        $queue = Mockery::mock(RedisQueue::class);
        $queue->shouldAllowMockingProtectedMethods();
        $queue
            ->shouldReceive('availableAt')
            ->once()
            ->andReturn($now);
        $queue
            ->shouldReceive('getConnection')
            ->once()
            ->andReturn($connection);
        $queue
            ->shouldReceive('getQueue')
            ->with($queueName)
            ->andReturn($queueName);

        $queueJob = Mockery::mock(RedisJob::class);
        $queueJob
            ->shouldReceive('getRedisQueue')
            ->once()
            ->andReturn($queue);
        $queueJob
            ->shouldReceive('getQueue')
            ->once()
            ->andReturn($queueName);
        $queueJob
            ->shouldReceive('getReservedJob')
            ->once()
            ->andReturn($uuid);

        $job = Mockery::mock($class);
        $job
            ->shouldReceive('getJob')
            ->once()
            ->andReturn($queueJob);

        $pinger = Mockery::mock(Pinger::class);
        $pinger->shouldAllowMockingProtectedMethods();
        $pinger->makePartial();

        $pinger->prolong($job);
    }

    /**
     * @covers ::prolong
     *
     * @dataProvider dataProviderJobClass
     *
     * @param class-string<CronJob|Job> $class
     */
    public function testProlongNotRedisJob(string $class): void {
        $queueJob = Mockery::mock(QueueJob::class);
        $job      = Mockery::mock($class);
        $job
            ->shouldReceive('getJob')
            ->once()
            ->andReturn($queueJob);

        $pinger = Mockery::mock(Pinger::class);
        $pinger->shouldAllowMockingProtectedMethods();
        $pinger->makePartial();

        $pinger->prolong($job);
    }
    //</editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array<string>>
     */
    public function dataProviderJobClass(): array {
        return [
            Job::class     => [Job::class],
            CronJob::class => [CronJob::class],
        ];
    }
    // </editor-fold>
}
