<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer;

use App\Services\DataLoader\Normalizer\Normalizers\BoolNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\ColorNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\DecimalNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\FloatNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\IntNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\NameNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UnsignedNormalizer;
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
     * @covers ::decimal
     */
    public function testDecimal(): void {
        $number     = Mockery::mock(DecimalNormalizer::class);
        $normalizer = new class($number) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected DecimalNormalizer $decimal,
            ) {
                // empty
            }
        };

        $number->shouldReceive('normalize')->once()->andReturns();

        $normalizer->decimal('value');
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
        $text       = Mockery::mock(TextNormalizer::class);
        $normalizer = new class($text) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected TextNormalizer $text,
            ) {
                // empty
            }
        };

        $text->shouldReceive('normalize')->once()->andReturns();

        $normalizer->text('value');
    }

    /**
     * @covers ::name
     */
    public function testName(): void {
        $name       = Mockery::mock(NameNormalizer::class);
        $normalizer = new class($name) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected NameNormalizer $name,
            ) {
                // empty
            }
        };

        $name->shouldReceive('normalize')->once()->andReturns();

        $normalizer->name('value');
    }

    /**
     * @covers ::int
     */
    public function testInt(): void {
        $int        = Mockery::mock(IntNormalizer::class);
        $normalizer = new class($int) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected IntNormalizer $int,
            ) {
                // empty
            }
        };

        $int->shouldReceive('normalize')->once()->andReturns();

        $normalizer->int('value');
    }

    /**
     * @covers ::float
     */
    public function testFloat(): void {
        $float      = Mockery::mock(FloatNormalizer::class);
        $normalizer = new class($float) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected FloatNormalizer $float,
            ) {
                // empty
            }
        };

        $float->shouldReceive('normalize')->once()->andReturns();

        $normalizer->float('value');
    }

    /**
     * @covers ::unsigned
     */
    public function testUnsigned(): void {
        $unsigned   = Mockery::mock(UnsignedNormalizer::class);
        $normalizer = new class($unsigned) extends Normalizer {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected UnsignedNormalizer $unsigned,
            ) {
                // empty
            }
        };

        $unsigned->shouldReceive('normalize')->once()->andReturns();

        $normalizer->unsigned(null);
    }
}
