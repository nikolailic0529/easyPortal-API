<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Commands;

use Illuminate\Contracts\Console\Kernel;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Keycloak\Commands\UsersSync
 */
class UsersSyncTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:keycloak-users-sync');
    }

    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertArrayHasKey('ep:keycloak-users-sync', $this->app->make(Kernel::class)->all());
    }
}
