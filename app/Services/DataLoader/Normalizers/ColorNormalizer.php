<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use App\Rules\Color;
use Illuminate\Contracts\Validation\Factory;

use function is_string;
use function trim;

class ColorNormalizer implements ValueNormalizer {
    public function __construct(
        protected Factory $validator,
    ) {
        // empty
    }

    public function normalize(mixed $value): ?string {
        // Parse
        if (is_string($value)) {
            $value     = trim($value);
            $validator = $this->validator->make(['value' => $value], ['value' => [new Color()]]);

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
