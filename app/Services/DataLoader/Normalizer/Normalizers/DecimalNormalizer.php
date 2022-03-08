<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Services\DataLoader\Normalizer\ValueNormalizer;

use function number_format;

class DecimalNormalizer implements ValueNormalizer {
    public function __construct(
        protected FloatNormalizer $normalizer,
    ) {
        // empty
    }

    public function normalize(mixed $value): ?string {
        $value = $this->normalizer->normalize($value);

        if ($value !== null) {
            $value = number_format($value, 2, '.', '');
        }

        return $value;
    }
}
