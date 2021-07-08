<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Jobs\SyncPermissionsCronJob
 */
class SyncPermissionsCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(SyncPermissionsCronJob::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $job    = $this->app->make(SyncPermissionsCronJob::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('ep:keycloak-sync-permissions')
            ->once();

        $job($kernel);
    }
}
