<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizers\UnsignedFloatNormalizer
 */
class UnsignedFloatNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::normalize
     *
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(mixed $expected, mixed $value): void {
        self::assertEquals($expected, UnsignedFloatNormalizer::normalize($value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderNormalize(): array {
        return [
            'null'                => [null, null],
            'bool'                => [null, true],
            'array'               => [null, [4, 3, 2.2]],
            'float'               => [123_213.3566, 123_213.3566],
            'float (negative)'    => [0.00, -123.45],
            'int'                 => [123_213.00, 123_213],
            'int (negative)'      => [0.00, -1],
            'string'              => [123_213.36, '123,213.36'],
            'string no decimal'   => [123_213.00, '123,213'],
            'string not a number' => [null, 'string'],
        ];
    }
    // </editor-fold>
}
