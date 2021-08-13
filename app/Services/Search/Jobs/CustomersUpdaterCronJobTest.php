<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Search\Jobs\CustomersUpdaterCronJob
 */
class CustomersUpdaterCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(CustomersUpdaterCronJob::class);
    }
}
