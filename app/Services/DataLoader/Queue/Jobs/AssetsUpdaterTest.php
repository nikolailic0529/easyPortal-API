<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Queue\Jobs\AssetsUpdater
 */
class AssetsUpdaterTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(AssetsUpdater::class);
    }
}
