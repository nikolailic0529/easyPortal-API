<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use function preg_replace;
use function trim;

class StringNormalizer implements Normalizer {
    public function normalize(mixed $value): string {
        $value = (string) $value;
        $value = trim($value);
        $value = preg_replace('/[\s]+/ui', ' ', $value);

        return $value;
    }
}
