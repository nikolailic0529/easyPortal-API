<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Queue\Jobs\AssetsImporter
 */
class AssetsImporterTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(AssetsImporter::class);
    }
}
