<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use App\Services\DataLoader\Normalizer\ValueNormalizer;

use function is_null;
use function preg_replace;
use function trim;

class StringNormalizer implements ValueNormalizer {
    /**
     * @return ($value is string ? string : string|null)
     */
    public function normalize(mixed $value): ?string {
        if (!is_null($value)) {
            $value = (string) $value;
            $value = (string) preg_replace('/[\s\x00]+/ui', ' ', $value);
            $value = trim($value);
        }

        return $value;
    }
}
