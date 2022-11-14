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
        $actual     = new Document($json);
        $properties = Document::getPropertiesNames();

        self::assertEquals(array_keys($json), $properties);
        self::assertInstanceOf(DocumentVendorSpecificField::class, $actual->vendorSpecificFields);
        self::assertInstanceOf(CompanyContactPerson::class, $actual->contactPersons[0] ?? null);
        self::assertInstanceOf(DocumentEntry::class, $actual->documentEntries[0] ?? null);
        self::assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
