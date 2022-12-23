<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Utils\JsonObject\Normalizer;

use function number_format;

class DecimalNormalizer implements Normalizer {
    public static function normalize(mixed $value): ?string {
        $value = FloatNormalizer::normalize($value);

        if ($value !== null) {
            $value = number_format($value, 2, '.', '');
        }

        return $value;
    }
}
