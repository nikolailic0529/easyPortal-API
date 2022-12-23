<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\Document
 */
class DocumentTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new Document($json);
        $properties = Document::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertInstanceOf(DocumentVendorSpecificField::class, $actual->vendorSpecificFields);
        self::assertInstanceOf(CompanyContactPerson::class, $actual->contactPersons[0] ?? null);
        self::assertInstanceOf(DocumentEntry::class, $actual->documentEntries[0] ?? null);
    }
}
