<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Location;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Rules\MapLevel
 */
class MapLevelTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::passes
     *
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $this->assertEquals($expected, (new MapLevel())->passes('test', $value));
    }

    /**
     * @covers ::message
     */
    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.map_level' => 'message validation.map_level',
                ],
            ];
        });

        $this->assertEquals('message validation.map_level', (new MapLevel())->message());
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderPasses(): array {
        return [
            'bool'    => [false, false],
            '0'       => [false, 0],
            'min'     => [true, 1],
            'max'     => [true, Location::GEOHASH_LENGTH],
            'max + 1' => [false, Location::GEOHASH_LENGTH + 1],
        ];
    }
    // </editor-fold>
}
