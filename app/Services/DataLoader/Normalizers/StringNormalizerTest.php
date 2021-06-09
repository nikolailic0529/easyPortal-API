<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizers\StringNormalizer
 */
class StringNormalizerTest extends TestCase {
    /**
     * @covers ::normalize
     */
    public function testNormalize(): void {
        $this->assertEquals('Fsfsd dsfd dSfd', (new StringNormalizer())->normalize(" Fsfsd  dsfd  \x00  dSfd "));
        $this->assertNull((new StringNormalizer())->normalize(null));
    }
}
