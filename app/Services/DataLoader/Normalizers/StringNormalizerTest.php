<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\DataLoader\Normalizers\StringNormalizer
 */
class StringNormalizerTest extends TestCase {
    public function testNormalize(): void {
        self::assertEquals('Fsfsd dsfd dSfd', StringNormalizer::normalize(" Fsfsd   dsfd  \x00  dSfd\x00  "));
        self::assertEquals('0', StringNormalizer::normalize('0'));
        self::assertNull(StringNormalizer::normalize(null));
    }
}
