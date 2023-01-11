<?php declare(strict_types = 1);

namespace App\Models\Enums;

use App\Models\Reseller;
use Tests\TestCase;

use function array_map;
use function array_values;

/**
 * @internal
 * @covers \App\Models\Enums\OrganizationType
 */
class OrganizationTypeTest extends TestCase {
    /**
     * @coversNothing
     */
    public function testMembers(): void {
        $actual   = array_values(array_map('strval', OrganizationType::getValues()));
        $expected = [
            (new Reseller())->getMorphClass(),
        ];

        self::assertEqualsCanonicalizing($expected, $actual);
    }
}
