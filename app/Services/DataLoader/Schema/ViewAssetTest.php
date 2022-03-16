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

        self::assertEquals(array_keys($json), $properties);
        self::assertInstanceOf(ViewAssetDocument::class, $actual->assetDocument[0]);
        self::assertInstanceOf(ViewDocument::class, $actual->assetDocument[0]->document);
        self::assertInstanceOf(CoverageStatusCheck::class, $actual->coverageStatusCheck);
        self::assertInstanceOf(CoverageEntry::class, $actual->coverageStatusCheck->coverageEntries[0]);
        self::assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
