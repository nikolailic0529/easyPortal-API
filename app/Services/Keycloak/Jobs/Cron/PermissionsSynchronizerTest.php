<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Jobs\Cron;

use App\Services\Keycloak\Commands\PermissionsSync;
use Illuminate\Contracts\Console\Kernel;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Keycloak\Jobs\Cron\PermissionsSynchronizer
 */
class PermissionsSynchronizerTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(PermissionsSynchronizer::class);
    }

    public function testHandle(): void {
        $job    = $this->app->make(PermissionsSynchronizer::class);
        $kernel = Mockery::mock(Kernel::class);

        $kernel
            ->shouldReceive('call')
            ->with(PermissionsSync::class)
            ->once();

        $job($kernel);
    }
}
