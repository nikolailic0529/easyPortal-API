<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\ViewDocument
 */
class ViewDocumentTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = new ViewDocument($json);
        $properties = ViewDocument::getPropertiesNames();

        $this->assertEquals(array_keys($json), $properties);
        $this->assertInstanceOf(DocumentVendorSpecificField::class, $actual->vendorSpecificFields);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
