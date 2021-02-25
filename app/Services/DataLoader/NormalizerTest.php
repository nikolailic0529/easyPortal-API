<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Normalizers\KeyNormalizer;
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
     * @covers ::key
     */
    public function testKey(): void {
        $key        = Mockery::mock(KeyNormalizer::class);
        $normalizer = new Normalizer($key, new UuidNormalizer(), new StringNormalizer());

        $key->shouldReceive('normalize')->once()->andReturns();

        $normalizer->key('value');
    }

    /**
     * @covers ::uuid
     */
    public function testUuid(): void {
        $uuid       = Mockery::mock(UuidNormalizer::class);
        $normalizer = new Normalizer(new KeyNormalizer(), $uuid, new StringNormalizer());

        $uuid->shouldReceive('normalize')->once()->andReturns();

        $normalizer->uuid('value');
    }

    /**
     * @covers ::string
     */
    public function testString(): void {
        $string     = Mockery::mock(StringNormalizer::class);
        $normalizer = new Normalizer(new KeyNormalizer(), new UuidNormalizer(), $string);

        $string->shouldReceive('normalize')->once()->andReturns();

        $normalizer->string('value');
    }
}
