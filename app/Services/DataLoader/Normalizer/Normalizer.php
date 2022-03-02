<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer;

use App\Services\DataLoader\Container\Singleton;
use App\Services\DataLoader\Normalizer\Normalizers\BoolNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\ColorNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\NameNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\NumberNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\TextNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UuidNormalizer;
use DateTimeInterface;

class Normalizer implements Singleton {
    public function __construct(
        protected UuidNormalizer $uuid,
        protected StringNormalizer $string,
        protected DateTimeNormalizer $datetime,
        protected NumberNormalizer $number,
        protected BoolNormalizer $boolean,
        protected ColorNormalizer $color,
        protected TextNormalizer $text,
        protected NameNormalizer $name,
    ) {
        // empty
    }

    public function uuid(mixed $value): string {
        return $this->uuid->normalize($value);
    }

    public function string(mixed $value): ?string {
        return $this->string->normalize($value);
    }

    public function text(mixed $value): ?string {
        return $this->text->normalize($value);
    }

    public function datetime(mixed $value): ?DateTimeInterface {
        return $this->datetime->normalize($value);
    }

    public function number(mixed $value): ?string {
        return $this->number->normalize($value);
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

    public function name(mixed $value): ?string {
        return $this->name->normalize($value);
    }
}
