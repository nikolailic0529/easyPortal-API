<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Jobs\SyncPermissions
 */
class SyncPermissionsTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(SyncPermissions::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $job    = $this->app->make(SyncPermissions::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('ep:keycloak-sync-permissions')
            ->once();

        $job->handle($kernel);
    }
}
