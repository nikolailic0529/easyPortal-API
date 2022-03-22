<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizer\Normalizers\NameNormalizer
 */
class NameNormalizerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::normalize
     *
     * @dataProvider dataProviderNormalize
     */
    public function testNormalize(mixed $expected, mixed $value): void {
        self::assertEquals($expected, $this->app->make(NameNormalizer::class)->normalize($value));
    }
    // </editor-fold>

    // <editor-fold desc="DataProvider">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderNormalize(): array {
        return [
            ['Delivery Contract', 'Delivery contract'],
            ['K3 - Hp Technology Software', 'K3 - HP Technology Software'],
            ['Reseller', 'RESELLER'],
            ['Software Contact', 'SOFTWARE_CONTACT'],
            ['Expires In 30 Days', 'EXPIRES_IN_30_DAYS'],
        ];
    }
    // </editor-fold>
}
