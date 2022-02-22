<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs\Cron;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\KeyCloak\Jobs\Cron\UsersSynchronizer
 */
class UsersSynchronizerTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(UsersSynchronizer::class);
    }
}
