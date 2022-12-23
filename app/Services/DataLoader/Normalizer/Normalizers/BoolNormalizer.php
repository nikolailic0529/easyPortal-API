<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Utils\JsonObject\Normalizer;

use function filter_var;
use function is_bool;
use function is_string;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;

class BoolNormalizer implements Normalizer {
    public static function normalize(mixed $value): ?bool {
        // Parse
        if (is_bool($value)) {
            // nothing to do
        } elseif (is_string($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        } else {
            $value = null;
        }

        // Return
        return $value;
    }
}
