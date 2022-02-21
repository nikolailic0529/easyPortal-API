<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs\Cron;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\Cron\CustomersRecalculator
 */
class CustomersRecalculatorTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(CustomersRecalculator::class);
    }
}
