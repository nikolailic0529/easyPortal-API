<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizers\UuidNormalizer;

class Normalizer {
    protected StringNormalizer $string;
    protected UuidNormalizer   $uuid;

    public function __construct(StringNormalizer $string, UuidNormalizer $uuid) {
        $this->string = $string;
        $this->uuid   = $uuid;
    }

    public function uuid(mixed $value): string {
        return $this->uuid->normalize($value);
    }

    public function string(mixed $value): string {
        return $this->string->normalize($value);
    }
}
