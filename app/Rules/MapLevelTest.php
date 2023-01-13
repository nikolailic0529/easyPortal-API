<?php declare(strict_types = 1);

namespace App\Rules;

use App\Models\Data\Location;
use Illuminate\Contracts\Validation\Factory;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Rules\MapLevel
 */
class MapLevelTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderPasses
     */
    public function testPasses(bool $expected, mixed $value): void {
        $rule   = $this->app->make(MapLevel::class);
        $actual = $rule->passes('test', $value);
        $passes = !$this->app->make(Factory::class)
            ->make(['value' => $value], ['value' => $rule])
            ->fails();

        self::assertEquals($expected, $actual);
        self::assertEquals($expected, $passes);
    }

    public function testMessage(): void {
        $this->setTranslations(static function (TestCase $case, string $locale): array {
            return [
                $locale => [
                    'validation.map_level' => 'message validation.map_level',
                ],
            ];
        });

        self::assertEquals('message validation.map_level', (new MapLevel())->message());
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
            '``'      => [false, ''],
        ];
    }
    // </editor-fold>
}
