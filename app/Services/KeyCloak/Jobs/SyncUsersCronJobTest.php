<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

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
}
