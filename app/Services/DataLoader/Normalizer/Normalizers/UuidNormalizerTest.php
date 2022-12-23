<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer
 */
class UuidNormalizerTest extends TestCase {
    /**
     * @covers ::normalize
     */
    public function testNormalize(): void {
        self::assertEquals(
            '1151886d-c5fb-40d3-a5f4-33b5ca38ff85',
            UuidNormalizer::normalize(' 1151886D-C5FB-40D3-A5F4-33B5CA38FF85 '),
        );
    }
}
