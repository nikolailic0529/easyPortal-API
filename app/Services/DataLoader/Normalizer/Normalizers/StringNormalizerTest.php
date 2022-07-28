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
        $normalizer = new StringNormalizer();

        self::assertEquals('Fsfsd dsfd dSfd', $normalizer->normalize(" Fsfsd   dsfd  \x00  dSfd\x00  "));
        self::assertEquals('0', $normalizer->normalize('0'));
        self::assertNull($normalizer->normalize(null));
    }
}
