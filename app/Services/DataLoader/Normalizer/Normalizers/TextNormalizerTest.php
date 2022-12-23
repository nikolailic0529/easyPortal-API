<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer
 */
class TextNormalizerTest extends TestCase {
    /**
     * @covers ::normalize
     */
    public function testNormalize(): void {
        self::assertEquals(
            "Fsfsd  dsfd      dS \n sdfsdf \n ssdfsf \n fd",
            TextNormalizer::normalize(" Fsfsd  dsfd  \x00   dS \n sdfsdf \r\n ssdfsf \n\r fd\x00 "),
        );
        self::assertNull(TextNormalizer::normalize(null));
    }
}
