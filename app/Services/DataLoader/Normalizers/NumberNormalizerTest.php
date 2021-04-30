<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizers\NumberNormalizer
 */
class NumberNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::normalize
     *
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(mixed $expected, mixed $value): void {
        $this->assertEquals($expected, (new NumberNormalizer())->normalize($value));
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
            'float'               => ['123213.36', 123_213.3566],
            'int'                 => ['123213.00', 123_213],
            'negative'            => ['-1.00', -1],
            'string'              => ['123213.36', '123,213.36'],
            'string no decimal'   => ['123213.00', '123,213'],
            'string not a number' => [null, 'string'],
        ];
    }
    // </editor-fold>
}