<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Jobs\SyncUsersCronJob
 */
class SyncUsersCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(SyncUsersCronJob::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $job    = $this->app->make(SyncUsersCronJob::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('ep:keycloak-sync-users')
            ->once();

        $job($kernel);
    }
}
