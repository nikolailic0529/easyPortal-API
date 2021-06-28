<?php declare(strict_types = 1);

namespace App\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Jobs\HorizonSnapshotCronJob
 */
class HorizonSnapshotCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(HorizonSnapshotCronJob::class);
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

        $this->app->make(HorizonSnapshotCronJob::class)->handle($kernel);
    }
}
