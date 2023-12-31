<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Normalizers\UnsignedIntNormalizer
 */
class UnsignedIntNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(mixed $expected, mixed $value): void {
        self::assertEquals($expected, UnsignedIntNormalizer::normalize($value));
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
            'float'               => [123_213, 123_213.3566],
            'float (negative)'    => [0, -123.45],
            'int'                 => [123_213, 123_213],
            'int (negative)'      => [0, -1],
            'string'              => [123_214, '123,213.66'],
            'string no decimal'   => [123_213, '123,213'],
            'string not a number' => [null, 'string'],
        ];
    }
    // </editor-fold>
}
