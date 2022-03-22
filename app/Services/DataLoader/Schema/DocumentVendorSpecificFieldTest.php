<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\DocumentVendorSpecificField
 */
class DocumentVendorSpecificFieldTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = new DocumentVendorSpecificField($json);
        $properties = DocumentVendorSpecificField::getPropertiesNames();

        self::assertEquals(array_keys($json), $properties);
        self::assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
