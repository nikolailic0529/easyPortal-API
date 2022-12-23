<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function reset;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Schema\BrandingData
 */
class BrandingDataTest extends TestCase {
    /**
     * @covers ::create
     */
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys       = array_keys($json);
        $actual     = new BrandingData($json);
        $properties = BrandingData::getPropertiesNames();

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertCount(1, $actual->mainHeadingText);
        self::assertInstanceOf(TranslationText::class, reset($actual->mainHeadingText));
        self::assertCount(1, $actual->underlineText);
        self::assertInstanceOf(TranslationText::class, reset($actual->underlineText));
    }
}
