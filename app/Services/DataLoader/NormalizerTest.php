<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizers\KeyNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;
use Illuminate\Config\Repository;
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
        $config     = new Repository();
        $normalizer = new Normalizer(
            $key,
            new UuidNormalizer(),
            new StringNormalizer(),
            new DateTimeNormalizer($config),
        );

        $key->shouldReceive('normalize')->once()->andReturns();

        $normalizer->key('value');
    }

    /**
     * @covers ::uuid
     */
    public function testUuid(): void {
        $uuid       = Mockery::mock(UuidNormalizer::class);
        $config     = new Repository();
        $normalizer = new Normalizer(
            new KeyNormalizer(),
            $uuid,
            new StringNormalizer(),
            new DateTimeNormalizer($config),
        );

        $uuid->shouldReceive('normalize')->once()->andReturns();

        $normalizer->uuid('value');
    }

    /**
     * @covers ::string
     */
    public function testString(): void {
        $string     = Mockery::mock(StringNormalizer::class);
        $config     = new Repository();
        $normalizer = new Normalizer(
            new KeyNormalizer(),
            new UuidNormalizer(),
            $string,
            new DateTimeNormalizer($config),
        );

        $string->shouldReceive('normalize')->once()->andReturns();

        $normalizer->string('value');
    }

    /**
     * @covers ::datetime
     */
    public function testDatetime(): void {
        $datetime   = Mockery::mock(DateTimeNormalizer::class);
        $normalizer = new Normalizer(new KeyNormalizer(), new UuidNormalizer(), new StringNormalizer(), $datetime);

        $datetime->shouldReceive('normalize')->once()->andReturns();

        $normalizer->datetime('value');
    }
}
