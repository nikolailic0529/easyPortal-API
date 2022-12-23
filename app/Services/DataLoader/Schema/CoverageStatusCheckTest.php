<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function reset;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\CoverageStatusCheck
 */
class CoverageStatusCheckTest extends TestCase {
    /**
     * @covers ::create
     */
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
