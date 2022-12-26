<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use App\Utils\JsonObject\Normalizer;

use function is_int;
use function round;

class IntNormalizer implements Normalizer {
    public static function normalize(mixed $value): ?int {
        if (!is_int($value)) {
            $value = FloatNormalizer::normalize($value);
        }

        if ($value !== null) {
            $value = (int) round($value);
        }

        return $value;
    }
}
