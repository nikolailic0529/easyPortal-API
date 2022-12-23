<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Utils\JsonObject\Normalizer;

use function filter_var;
use function is_float;
use function is_int;
use function is_string;

use const FILTER_FLAG_ALLOW_THOUSAND;
use const FILTER_VALIDATE_FLOAT;

class FloatNormalizer implements Normalizer {
    public static function normalize(mixed $value): ?float {
        // Parse
        if (is_int($value) || is_float($value)) {
            $value = (float) $value;
        } elseif (is_string($value)) {
            $value = filter_var($value, FILTER_VALIDATE_FLOAT, [
                'flags' => FILTER_FLAG_ALLOW_THOUSAND,
            ]);

            if ($value === false) {
                $value = null;
            }
        } else {
            $value = null;
        }

        // Return
        return $value;
    }
}
