<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;
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
        $json       = $this->getTestData()->json();
        $actual     = new CoverageStatusCheck($json);
        $properties = CoverageStatusCheck::getPropertiesNames();

        $this->assertEquals(array_keys($json), $properties);
        $this->assertCount(2, $actual->coverageEntries);
        $this->assertInstanceOf(CoverageEntry::class, reset($actual->coverageEntries));
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
