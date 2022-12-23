<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\ViewAssetDocument
 */
class ViewAssetDocumentTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new ViewAssetDocument($json);
        $properties = ViewAssetDocument::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertInstanceOf(ViewDocument::class, $actual->document);
        self::assertInstanceOf(ViewCompany::class, $actual->reseller);
        self::assertInstanceOf(ViewCompany::class, $actual->customer);
    }
}
