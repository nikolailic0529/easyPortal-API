<?php declare(strict_types = 1);

namespace App\Services\Queue;

use Illuminate\Support\Facades\Date;
use Laravel\Horizon\Contracts\JobRepository;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;
use Mockery;
use Tests\TestCase;

use function json_encode;
use function microtime;
use function str_replace;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\Queue
 */
class QueueTest extends TestCase {
    /**
     * @covers ::getState
     */
    public function testGetState(): void {
        // Prepare
        $reserved = str_replace(',', '.', (string) microtime(true));
        $pushed   = str_replace(',', '.', (string) microtime(true));
        $a        = Mockery::mock(Job::class);
        $b        = Mockery::mock(Job::class);
        $c        = Mockery::mock(Job::class, NamedJob::class, Progressable::class);
        $c
            ->shouldReceive('displayName')
            ->once()
            ->andReturn('b');

        $qaa = new QueueJob([
            'id'          => $this->faker->uuid,
            'name'        => $a::class,
            'status'      => QueueJob::STATUS_RESERVED,
            'payload'     => json_encode(['pushedAt' => $pushed]),
            'reserved_at' => $reserved,
        ]);
        $qab = new QueueJob([
            'id'      => $this->faker->uuid,
            'name'    => $a::class,
            'status'  => QueueJob::STATUS_PENDING,
            'payload' => json_encode(['pushedAt' => $pushed]),
        ]);
        $qb  = new QueueJob([
            'id'      => $this->faker->uuid,
            'name'    => 'b',
            'status'  => QueueJob::STATUS_COMPLETED,
            'payload' => json_encode(['pushedAt' => $pushed]),
        ]);
        $qc  = new QueueJob([
            'id'      => $this->faker->uuid,
            'name'    => 'c',
            'status'  => QueueJob::STATUS_PENDING,
            'payload' => json_encode(['pushedAt' => $pushed]),
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
            ->andReturn([$qb, $qc]);
        $repository
            ->shouldReceive('getPending')
            ->with(4)
            ->once()
            ->andReturn([]);

        // Test
        $actual   = (new Queue($this->app, $repository))->getState([$a, $b, $c]);
        $expected = [
            $a::class => [
                new State(
                    $qab->id,
                    $qab->name,
                    false,
                    Date::createFromTimestamp((float) $pushed),
                    null,
                ),
                new State(
                    $qaa->id,
                    $qaa->name,
                    true,
                    Date::createFromTimestamp((float) $pushed),
                    Date::createFromTimestamp((float) $reserved),
                ),
            ],
            'b'       => [
                new State(
                    $qb->id,
                    $qb->name,
                    false,
                    Date::createFromTimestamp((float) $pushed),
                    null,
                ),
            ],
        ];

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::getState
     */
    public function testGetStateEmptyJobs(): void {
        // Prepare
        $repository = Mockery::mock(JobRepository::class);
        $repository
            ->shouldReceive('getPending')
            ->never();

        // Test
        $actual   = (new Queue($this->app, $repository))->getState([]);
        $expected = [];

        $this->assertEquals($expected, $actual);
    }
}
