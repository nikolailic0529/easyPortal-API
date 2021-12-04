<?php declare(strict_types = 1);

namespace App\Services\Queue\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Queue\Jobs\SnapshotCronJob
 */
class SnapshotCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(SnapshotCronJob::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('horizon:snapshot')
            ->once();

        ($this->app->make(SnapshotCronJob::class))($kernel);
    }
}
