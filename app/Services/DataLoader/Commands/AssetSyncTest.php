<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Commands;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Commands\AssetSync
 */
class AssetSyncTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testCommand(): void {
        self::assertCommandDescription('ep:data-loader-asset-sync');
    }
}
