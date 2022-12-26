<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizers\ColorNormalizer
 */
class ColorNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::normalize
     *
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(mixed $expected, mixed $value): void {
        self::assertEquals($expected, ColorNormalizer::normalize($value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderNormalize(): array {
        return [
            'null'             => [null, null],
            'bool'             => [null, true],
            'array'            => [null, [4, 3, 2.2]],
            'float'            => [null, 123_213.3566],
            'int'              => [null, 123_213],
            'string not color' => [null, '123,213.36'],
            'color'            => ['#FF00FF', '#FF00FF'],
            'color (trim)'     => ['#FF00FF', ' #FF00FF '],
        ];
    }
    // </editor-fold>
}
