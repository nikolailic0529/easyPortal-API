<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer
 */
class StringNormalizerTest extends TestCase {
    /**
     * @covers ::normalize
     */
    public function testNormalize(): void {
        self::assertEquals('Fsfsd dsfd dSfd', (new StringNormalizer())->normalize(" Fsfsd   dsfd  \x00  dSfd\x00  "));
        self::assertNull((new StringNormalizer())->normalize(null));
    }
}
