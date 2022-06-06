<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer;

use App\Services\DataLoader\Container\Singleton;
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
use Carbon\CarbonImmutable;

class Normalizer implements Singleton {
    public function __construct(
        protected UuidNormalizer $uuid,
        protected StringNormalizer $string,
        protected DateTimeNormalizer $datetime,
        protected DecimalNormalizer $decimal,
        protected BoolNormalizer $boolean,
        protected ColorNormalizer $color,
        protected TextNormalizer $text,
        protected NameNormalizer $name,
        protected IntNormalizer $int,
        protected FloatNormalizer $float,
        protected UnsignedNormalizer $unsigned,
    ) {
        // empty
    }

    public function uuid(mixed $value): string {
        return $this->uuid->normalize($value);
    }

    /**
     * @return ($value is string ? string : string|null)
     */
    public function string(mixed $value): ?string {
        return $this->string->normalize($value);
    }

    /**
     * @return ($value is string ? string : string|null)
     */
    public function text(mixed $value): ?string {
        return $this->text->normalize($value);
    }

    public function datetime(mixed $value): ?CarbonImmutable {
        return $this->datetime->normalize($value);
    }

    public function decimal(mixed $value): ?string {
        return $this->decimal->normalize($value);
    }

    public function boolean(mixed $value): ?bool {
        return $this->boolean->normalize($value);
    }

    public function coordinate(mixed $value): ?string {
        return $this->string($value);
    }

    public function color(mixed $value): ?string {
        return $this->color->normalize($value);
    }

    /**
     * @return ($value is string ? string : string|null)
     */
    public function name(mixed $value): ?string {
        return $this->name->normalize($value);
    }

    public function int(mixed $value): ?int {
        return $this->int->normalize($value);
    }

    public function float(mixed $value): ?float {
        return $this->float->normalize($value);
    }

    /**
     * @return ($value is float ? float : ($value is int ? int : null))
     */
    public function unsigned(mixed $value): mixed {
        return $this->unsigned->normalize($value);
    }
}
