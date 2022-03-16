<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizer\Normalizers\IntNormalizer
 */
class IntNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::normalize
     *
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(mixed $expected, mixed $value): void {
        self::assertEquals($expected, $this->app->make(IntNormalizer::class)->normalize($value));
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
            'round'               => [123_214, 123_213.5],
            'int'                 => [123_213, 123_213],
            'negative'            => [-1, -1],
            'string'              => [123_213, '123,213.36'],
            'string no decimal'   => [123_213, '123,213'],
            'string not a number' => [null, 'string'],
        ];
    }
    // </editor-fold>
}
