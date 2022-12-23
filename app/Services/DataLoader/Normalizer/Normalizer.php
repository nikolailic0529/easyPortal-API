<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer;

use App\Services\DataLoader\Normalizer\Normalizers\DateTimeNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\FloatNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\IntNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;
use App\Services\DataLoader\Normalizer\Normalizers\UnsignedNormalizer;
use Carbon\CarbonImmutable;

/**
 * @deprecated fixme(DataLoader)!: Use {@see \App\Utils\JsonObject\JsonObjectNormalizer} instead
 */
class Normalizer {
    public function __construct() {
        // empty
    }

    /**
     * @return ($value is string ? string : string|null)
     */
    public function string(mixed $value): ?string {
        return StringNormalizer::normalize($value);
    }

    public function datetime(mixed $value): ?CarbonImmutable {
        return DateTimeNormalizer::normalize($value);
    }

    public function int(mixed $value): ?int {
        return IntNormalizer::normalize($value);
    }

    public function float(mixed $value): ?float {
        return FloatNormalizer::normalize($value);
    }

    /**
     * @return ($value is float ? float : ($value is int ? int : null))
     */
    public function unsigned(mixed $value): mixed {
        return UnsignedNormalizer::normalize($value);
    }
}
