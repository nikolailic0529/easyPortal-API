<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\DocumentsImporterCronJob
 */
class DocumentsImporterCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(DocumentsImporterCronJob::class);
    }
}