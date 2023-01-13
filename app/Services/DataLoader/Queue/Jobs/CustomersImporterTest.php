<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Queue\Jobs\CustomersImporter
 */
class CustomersImporterTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(CustomersImporter::class);
    }
}
