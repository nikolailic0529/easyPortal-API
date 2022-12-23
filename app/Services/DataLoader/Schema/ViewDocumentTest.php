<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\ViewDocument
 */
class ViewDocumentTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new ViewDocument($json);
        $properties = ViewDocument::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertInstanceOf(DocumentVendorSpecificField::class, $actual->vendorSpecificFields);
    }
}
