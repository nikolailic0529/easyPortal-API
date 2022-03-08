<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Services\DataLoader\Normalizer\ValueNormalizer;

use function is_int;
use function round;

class IntNormalizer implements ValueNormalizer {
    public function __construct(
        protected FloatNormalizer $normalizer,
    ) {
        // empty
    }

    public function normalize(mixed $value): ?int {
        if (!is_int($value)) {
            $value = $this->normalizer->normalize($value);
        }

        if ($value !== null) {
            $value = (int) round($value);
        }

        return $value;
    }
}
