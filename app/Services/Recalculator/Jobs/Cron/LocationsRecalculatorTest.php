<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Jobs\Cron;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Recalculator\Jobs\Cron\LocationsRecalculator
 */
class LocationsRecalculatorTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        $this->assertCronableRegistered(LocationsRecalculator::class);
    }
}
