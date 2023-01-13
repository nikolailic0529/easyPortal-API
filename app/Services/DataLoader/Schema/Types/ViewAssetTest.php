<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use Tests\TestCase;

use function array_keys;

/**
 * @internal
 * @covers \App\Services\DataLoader\Schema\Types\ViewAsset
 */
class ViewAssetTest extends TestCase {
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new ViewAsset($json);
        $properties = ViewAsset::getPropertiesNames();

        self::assertEquals($actual->id, $actual->getKey());
        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertInstanceOf(ViewAssetDocument::class, $actual->assetDocument[0] ?? null);
        self::assertInstanceOf(ViewDocument::class, $actual->assetDocument[0]->document);
        self::assertInstanceOf(CoverageStatusCheck::class, $actual->coverageStatusCheck);
        self::assertInstanceOf(CoverageEntry::class, $actual->coverageStatusCheck->coverageEntries[0] ?? null);
    }
}
