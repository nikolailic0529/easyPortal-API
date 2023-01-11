<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema\Types;

use Tests\TestCase;

use function array_keys;
use function reset;

/**
 * @internal
 * @covers \App\Services\DataLoader\Schema\Types\BrandingData
 */
class BrandingDataTest extends TestCase {
    public function testCreate(): void {
        $json = $this->getTestData()->json();

        self::assertIsArray($json);

        $keys            = array_keys($json);
        $actual          = new BrandingData($json);
        $properties      = BrandingData::getPropertiesNames();
        $underlineText   = (array) $actual->underlineText;
        $mainHeadingText = (array) $actual->mainHeadingText;

        self::assertEqualsCanonicalizing($keys, $properties);
        self::assertEqualsCanonicalizing($keys, array_keys($actual->getProperties()));
        self::assertCount(1, $mainHeadingText);
        self::assertInstanceOf(TranslationText::class, reset($mainHeadingText));
        self::assertCount(1, $underlineText);
        self::assertInstanceOf(TranslationText::class, reset($underlineText));
    }
}
