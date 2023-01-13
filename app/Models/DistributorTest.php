<?php declare(strict_types = 1);

namespace App\Models;

use Tests\TestCase;

use function get_defined_vars;

/**
 * @internal
 * @covers \App\Models\Distributor
 */
class DistributorTest extends TestCase {
    public function testDelete(): void {
        // Prepare
        $distributor = Distributor::factory()->create();

        // Test
        self::assertModelHasAllRelations($distributor);

        $distributor->delete();

        self::assertModelsTrashed(
            [
                'distributor' => true,
            ],
            get_defined_vars(),
        );
    }
}
