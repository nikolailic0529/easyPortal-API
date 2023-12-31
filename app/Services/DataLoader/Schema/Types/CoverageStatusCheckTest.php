<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use Tests\TestCase;

use function array_keys;
use function reset;

/**
 * @internal
 * @covers \App\Services\DataLoader\Schema\Types\CoverageStatusCheck
 */
class CoverageStatusCheckTest extends TestCase {
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new CoverageStatusCheck($json);
        $properties = CoverageStatusCheck::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertCount(2, $actual->coverageEntries);
        self::assertInstanceOf(CoverageEntry::class, reset($actual->coverageEntries));
    }
}
