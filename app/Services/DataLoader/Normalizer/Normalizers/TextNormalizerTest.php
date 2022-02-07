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
        $this->assertEquals(
            "Fsfsd  dsfd      dS \n sdfsdf \n ssdfsf \n fd",
            (new TextNormalizer())->normalize(" Fsfsd  dsfd  \x00   dS \n sdfsdf \r\n ssdfsf \n\r fd\x00 "),
        );
        $this->assertNull((new TextNormalizer())->normalize(null));
    }
}
