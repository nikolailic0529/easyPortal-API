<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\ViewAsset
 */
class ViewAssetTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new ViewAsset($json);
        $properties = ViewAsset::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertInstanceOf(ViewAssetDocument::class, $actual->assetDocument[0]);
        self::assertInstanceOf(ViewDocument::class, $actual->assetDocument[0]->document);
        self::assertInstanceOf(CoverageStatusCheck::class, $actual->coverageStatusCheck);
        self::assertInstanceOf(CoverageEntry::class, $actual->coverageStatusCheck->coverageEntries[0]);
    }
}
