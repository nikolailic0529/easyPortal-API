<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer;

use App\Services\DataLoader\Normalizer\Normalizers\BoolNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\ColorNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\NumberNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\DataLoader\Normalizer\Normalizer
 */
class NormalizerTest extends TestCase {
    /**
     * @covers ::uuid
     */
    public function testUuid(): void {
        $uuid       = Mockery::mock(UuidNormalizer::class);
        $normalizer = new class($uuid) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected UuidNormalizer $uuid,
            ) {
                // empty
            }
        };

        $uuid->shouldReceive('normalize')->once()->andReturns();

        $normalizer->uuid('value');
    }

    /**
     * @covers ::string
     */
    public function testString(): void {
        $string     = Mockery::mock(StringNormalizer::class);
        $normalizer = new class($string) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected StringNormalizer $string,
            ) {
                // empty
            }
        };

        $string->shouldReceive('normalize')->once()->andReturns();

        $normalizer->string('value');
    }

    /**
     * @covers ::datetime
     */
    public function testDatetime(): void {
        $datetime   = Mockery::mock(DateTimeNormalizer::class);
        $normalizer = new class($datetime) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected DateTimeNormalizer $datetime,
            ) {
                // empty
            }
        };

        $datetime->shouldReceive('normalize')->once()->andReturns();

        $normalizer->datetime('value');
    }

    /**
     * @covers ::number
     */
    public function testNumber(): void {
        $number     = Mockery::mock(NumberNormalizer::class);
        $normalizer = new class($number) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected NumberNormalizer $number,
            ) {
                // empty
            }
        };

        $number->shouldReceive('normalize')->once()->andReturns();

        $normalizer->number('value');
    }

    /**
     * @covers ::boolean
     */
    public function testBoolean(): void {
        $boolean    = Mockery::mock(BoolNormalizer::class);
        $normalizer = new class($boolean) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected BoolNormalizer $boolean,
            ) {
                // empty
            }
        };

        $boolean->shouldReceive('normalize')->once()->andReturns();

        $normalizer->boolean('value');
    }

    /**
     * @covers ::coordinate
     */
    public function testCoordinate(): void {
        $string     = Mockery::mock(StringNormalizer::class);
        $normalizer = new class($string) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected StringNormalizer $string,
            ) {
                // empty
            }
        };

        $string->shouldReceive('normalize')->once()->andReturns();

        $normalizer->coordinate('value');
    }

    /**
     * @covers ::color
     */
    public function testColor(): void {
        $color      = Mockery::mock(ColorNormalizer::class);
        $normalizer = new class($color) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected ColorNormalizer $color,
            ) {
                // empty
            }
        };

        $color->shouldReceive('normalize')->once()->andReturns();

        $normalizer->color('value');
    }

    /**
     * @covers ::text
     */
    public function testText(): void {
        $color      = Mockery::mock(TextNormalizer::class);
        $normalizer = new class($color) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected TextNormalizer $text,
            ) {
                // empty
            }
        };

        $color->shouldReceive('normalize')->once()->andReturns();

        $normalizer->text('value');
    }
}
