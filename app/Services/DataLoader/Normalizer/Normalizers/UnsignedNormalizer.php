<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Utils\JsonObject\Normalizer;

use function is_float;
use function is_int;
use function max;

class UnsignedNormalizer implements Normalizer {
    /**
     * @return ($value is float ? float : ($value is int ? int : null))
     */
    public static function normalize(mixed $value): mixed {
        if (is_float($value) || is_int($value)) {
            $value = max(0, $value);
        } else {
            $value = null;
        }

        return $value;
    }
}
