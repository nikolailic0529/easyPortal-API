<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\CompanyContactPerson
 */
class CompanyContactPersonTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new CompanyContactPerson($json);
        $properties = CompanyContactPerson::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
    }
}
