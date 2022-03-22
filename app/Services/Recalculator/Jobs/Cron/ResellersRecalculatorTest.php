<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs\Cron;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\Cron\ResellersRecalculator
 */
class ResellersRecalculatorTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(ResellersRecalculator::class);
    }
}
