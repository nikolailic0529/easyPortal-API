<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizers\BoolNormalizer
 */
class BoolNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::normalize
     *
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(mixed $expected, mixed $value): void {
        self::assertEquals($expected, BoolNormalizer::normalize($value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderNormalize(): array {
        return [
            'null'         => [null, null],
            'true'         => [true, true],
            'false'        => [false, false],
            'array'        => [null, [4, 3, 2.2]],
            'float'        => [null, 123_213.3566],
            'int'          => [null, 123_213],
            'string'       => [null, '123,213.36'],
            'string empty' => [false, ''],
            '"1"'          => [true, '1'],
            '"true"'       => [true, 'true'],
            '"on"'         => [true, 'on'],
            '"yes"'        => [true, 'yes'],
            '"0"'          => [false, '0'],
            '"false"'      => [false, 'false'],
            '"off"'        => [false, 'off'],
            '"no"'         => [false, 'no'],
        ];
    }
    // </editor-fold>
}
