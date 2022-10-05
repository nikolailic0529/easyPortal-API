<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Commands\DistributorsSync
 */
class DistributorsSyncTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:data-loader-distributors-sync');
    }
}
