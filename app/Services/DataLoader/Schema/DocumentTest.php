<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\Document
 */
class DocumentTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = Document::create($json);
        $properties = Document::getPropertiesNames();

        $this->assertEquals(array_keys($json), $properties);
        $this->assertInstanceOf(DocumentVendorSpecificField::class, $actual->vendorSpecificFields);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
