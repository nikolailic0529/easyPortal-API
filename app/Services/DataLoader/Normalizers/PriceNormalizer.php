<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use DateTimeInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Date;

use function filter_var;
use function is_float;
use function is_int;
use function is_null;
use function is_string;
use function number_format;
use function preg_match;
use function preg_replace;
use function round;
use function str_replace;

use const FILTER_FLAG_ALLOW_THOUSAND;
use const FILTER_VALIDATE_FLOAT;

class PriceNormalizer implements Normalizer {
    public function __construct() {
        // empty
    }

    public function normalize(mixed $value): ?string {
        // Parse
        if (is_int($value) || is_float($value)) {
            $value = round($value, 2);
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

        // Convert to string
        if (!is_null($value)) {
            $value = number_format($value, 2, '.', '');
        }

        // Return
        return $value;
    }
}
