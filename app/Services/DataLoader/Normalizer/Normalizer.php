<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer;

use App\Services\DataLoader\Normalizer\Normalizers\StringNormalizer;

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
}
