<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\Location
 */
class LocationTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $properties = Location::getPropertiesNames();

        $this->assertEquals(array_keys($json), $properties);

        $actual                 = Location::create($json);
        $expected               = new Location();
        $expected->zip          = $json['zip'];
        $expected->city         = $json['city'];
        $expected->address      = $json['address'];
        $expected->locationType = $json['locationType'];

        $this->assertEquals($expected, $actual);
    }
}
