<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\Asset
 */
class AssetTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = Asset::create($json);
        $properties = Asset::getPropertiesNames();

        $this->assertEquals(array_keys($json), $properties);
        $this->assertInstanceOf(Company::class, $actual->customer);
        $this->assertInstanceOf(AssetDocument::class, $actual->assetDocument[0]);
        $this->assertInstanceOf(Document::class, $actual->assetDocument[0]->document);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
