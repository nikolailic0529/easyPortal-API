<?php declare(strict_types = 1);

namespace App\Services\Search\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Queue\Jobs\AssetsIndexer
 */
class AssetsIndexerTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(AssetsIndexer::class);
    }
}
