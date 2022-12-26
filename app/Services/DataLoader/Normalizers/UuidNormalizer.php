<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use function mb_strtolower;

class UuidNormalizer extends StringNormalizer {
    public static function normalize(mixed $value): ?string {
        $value = parent::normalize($value);
        $value = $value ? mb_strtolower($value) : null;

        return $value;
    }
}
