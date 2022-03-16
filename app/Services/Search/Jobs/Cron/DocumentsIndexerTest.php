<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs\Cron;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Jobs\Cron\DocumentsIndexer
 */
class DocumentsIndexerTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(DocumentsIndexer::class);
    }
}
