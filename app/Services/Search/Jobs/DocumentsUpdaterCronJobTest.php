<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Jobs\DocumentsUpdaterCronJob
 */
class DocumentsUpdaterCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(DocumentsUpdaterCronJob::class);
    }
}
