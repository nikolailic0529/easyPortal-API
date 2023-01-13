<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @covers \App\Services\DataLoader\Schema\Types\CoverageEntry
 */
class CoverageEntryTest extends TestCase {
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new CoverageEntry($json);
        $properties = CoverageEntry::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing(array_keys($json), array_keys($actual->getProperties()));
    }
}
