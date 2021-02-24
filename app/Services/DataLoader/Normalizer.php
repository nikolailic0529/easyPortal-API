<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Normalizers\KeyNormalizer;
use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;

class Normalizer {
    public function __construct(
        protected KeyNormalizer $key,
        protected UuidNormalizer $uuid,
        protected StringNormalizer $string,
    ) {
        // empty
    }

    public function key(mixed $value): mixed {
        return $this->key->normalize($value);
    }

    public function uuid(mixed $value): string {
        return $this->uuid->normalize($value);
    }

    public function string(mixed $value): string {
        return $this->string->normalize($value);
    }
}
