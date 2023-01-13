<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Queue\Jobs\DocumentsImporter
 */
class DocumentsImporterTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(DocumentsImporter::class);
    }
}
