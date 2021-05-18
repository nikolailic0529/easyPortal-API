<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\AssetDocument
 */
class AssetDocumentTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = AssetDocument::create($json);
        $properties = AssetDocument::getPropertiesNames();

        $this->assertEquals(array_keys($json), $properties);
        $this->assertInstanceOf(Document::class, $actual->document);
        $this->assertInstanceOf(Company::class, $actual->reseller);
        $this->assertInstanceOf(Company::class, $actual->customer);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
