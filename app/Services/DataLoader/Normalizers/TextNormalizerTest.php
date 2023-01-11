<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Normalizers\TextNormalizer
 */
class TextNormalizerTest extends TestCase {
    public function testNormalize(): void {
        self::assertEquals(
            "Fsfsd  dsfd      dS \n sdfsdf \n ssdfsf \n fd",
            TextNormalizer::normalize(" Fsfsd  dsfd  \x00   dS \n sdfsdf \r\n ssdfsf \n\r fd\x00 "),
        );
        self::assertNull(TextNormalizer::normalize(null));
    }
}
