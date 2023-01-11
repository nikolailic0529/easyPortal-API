<?php declare(strict_types = 1);

namespace App\Services\Recalculator\Queue\Jobs;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Recalculator\Queue\Jobs\DocumentsRecalculator
 */
class DocumentsRecalculatorTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testRegistration(): void {
        self::assertCronableRegistered(DocumentsRecalculator::class);
    }
}
