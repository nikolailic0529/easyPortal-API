<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizer
 */
class NormalizerTest extends TestCase {
    /**
     * @covers ::uuid
     */
    public function testUuid(): void {
        $uuid       = Mockery::mock(UuidNormalizer::class);
        $normalizer = new Normalizer(new StringNormalizer(), $uuid);

        $uuid->shouldReceive('normalize')->once()->andReturns();

        $normalizer->uuid('value');
    }

    /**
     * @covers ::string
     */
    public function testString(): void {
        $string     = Mockery::mock(StringNormalizer::class);
        $normalizer = new Normalizer($string, new UuidNormalizer());

        $string->shouldReceive('normalize')->once()->andReturns();

        $normalizer->string('value');
    }
}
