<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use function max;

class UnsignedFloatNormalizer extends FloatNormalizer {
    public static function normalize(mixed $value): ?float {
        $value = parent::normalize($value);

        if ($value) {
            $value = max(0, $value);
        }

        return $value;
    }
}
