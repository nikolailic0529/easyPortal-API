<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Schema;

use Tests\TestCase;

use function array_keys;
use function json_encode;
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
        $json       = $this->getTestData()->json();
        $actual     = new BrandingData($json);
        $properties = BrandingData::getPropertiesNames();

        self::assertEqualsCanonicalizing(array_keys($json), $properties);
        self::assertCount(1, $actual->mainHeadingText);
        self::assertInstanceOf(TranslationText::class, reset($actual->mainHeadingText));
        self::assertCount(1, $actual->underlineText);
        self::assertInstanceOf(TranslationText::class, reset($actual->underlineText));
        self::assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
