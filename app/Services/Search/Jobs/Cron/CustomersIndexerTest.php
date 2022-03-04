<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs\Cron;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Jobs\Cron\CustomersIndexer
 */
class CustomersIndexerTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(CustomersIndexer::class);
    }
}
