<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs\Cron;

use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Jobs\Cron\PermissionsSynchronizer
 */
class PermissionsSynchronizerTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(PermissionsSynchronizer::class);
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void {
        $job    = $this->app->make(PermissionsSynchronizer::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with('ep:keycloak-permissions-sync')
            ->once();

        $job($kernel);
    }
}
