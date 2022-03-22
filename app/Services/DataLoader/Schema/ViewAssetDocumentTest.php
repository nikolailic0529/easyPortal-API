<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\ViewAssetDocument
 */
class ViewAssetDocumentTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = new ViewAssetDocument($json);
        $properties = ViewAssetDocument::getPropertiesNames();

        self::assertEquals(array_keys($json), $properties);
        self::assertInstanceOf(ViewDocument::class, $actual->document);
        self::assertInstanceOf(ViewCompany::class, $actual->reseller);
        self::assertInstanceOf(ViewCompany::class, $actual->customer);
        self::assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
