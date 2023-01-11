<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Jobs\Cron;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Keycloak\Jobs\Cron\UsersSynchronizer
 */
class UsersSynchronizerTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(UsersSynchronizer::class);
    }
}
