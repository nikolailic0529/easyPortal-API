<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Queue\Jobs\ResellersUpdater
 */
class ResellersUpdaterTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(ResellersUpdater::class);
    }
}
