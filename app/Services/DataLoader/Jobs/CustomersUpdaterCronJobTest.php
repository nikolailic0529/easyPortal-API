<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Jobs\CustomersUpdaterCronJob
 */
class CustomersUpdaterCronJobTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(CustomersUpdaterCronJob::class);
    }
}
