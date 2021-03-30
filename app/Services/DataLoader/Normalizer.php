<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizers\KeyNormalizer;
use App\Services\DataLoader\Normalizers\PriceNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;
use DateTimeInterface;

class Normalizer {
    public function __construct(
        protected KeyNormalizer $key,
        protected UuidNormalizer $uuid,
        protected StringNormalizer $string,
        protected DateTimeNormalizer $datetime,
        protected PriceNormalizer $price,
    ) {
        // empty
    }

    public function key(mixed $value): mixed {
        return $this->key->normalize($value);
    }

    public function uuid(mixed $value): string {
        return $this->uuid->normalize($value);
    }

    public function string(mixed $value): ?string {
        return $this->string->normalize($value);
    }

    public function datetime(mixed $value): ?DateTimeInterface {
        return $this->datetime->normalize($value);
    }

    public function price(mixed $value): ?string {
        return $this->price->normalize($value);
    }
}
