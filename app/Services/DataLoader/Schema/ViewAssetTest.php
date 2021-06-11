<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\ViewAsset
 */
class ViewAssetTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json       = $this->getTestData()->json();
        $actual     = new ViewAsset($json);
        $properties = ViewAsset::getPropertiesNames();

        $this->assertEquals(array_keys($json), $properties);
        $this->assertInstanceOf(ViewAssetDocument::class, $actual->assetDocument[0]);
        $this->assertInstanceOf(ViewDocument::class, $actual->assetDocument[0]->document);
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
