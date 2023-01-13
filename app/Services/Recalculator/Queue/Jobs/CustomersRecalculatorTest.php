<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Recalculator\Queue\Jobs\CustomersRecalculator
 */
class CustomersRecalculatorTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(CustomersRecalculator::class);
    }
}
