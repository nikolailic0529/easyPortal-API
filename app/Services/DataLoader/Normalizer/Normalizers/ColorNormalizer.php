<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Rules\Color;
use App\Utils\JsonObject\Normalizer;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory;

use function is_string;
use function trim;

class ColorNormalizer implements Normalizer {
    public static function normalize(mixed $value): ?string {
        // Parse
        if (is_string($value)) {
            $value     = trim($value);
            $validator = Container::getInstance()
                ->make(Factory::class)
                ->make(['value' => $value], ['value' => [new Color()]]);

            if ($validator->fails()) {
                $value = null;
            }
        } else {
            $value = null;
        }

        // Return
        return $value;
    }
}
