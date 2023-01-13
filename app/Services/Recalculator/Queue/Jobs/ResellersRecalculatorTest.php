<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Recalculator\Queue\Jobs\ResellersRecalculator
 */
class ResellersRecalculatorTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(ResellersRecalculator::class);
    }
}
