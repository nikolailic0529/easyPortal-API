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

        $this->assertEqualsCanonicalizing(array_keys($json), $properties);
        $this->assertCount(1, $actual->mainHeadingText);
        $this->assertInstanceOf(TranslationText::class, reset($actual->mainHeadingText));
        $this->assertCount(1, $actual->underlineText);
        $this->assertInstanceOf(TranslationText::class, reset($actual->underlineText));
        $this->assertJsonStringEqualsJsonString(
            json_encode($json),
            json_encode($actual),
        );
    }
}
