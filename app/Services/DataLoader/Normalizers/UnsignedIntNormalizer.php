<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use function max;

class UnsignedIntNormalizer extends IntNormalizer {
    public static function normalize(mixed $value): ?int {
        $value = parent::normalize($value);

        if ($value) {
            $value = max(0, $value);
        }

        return $value;
    }
}
