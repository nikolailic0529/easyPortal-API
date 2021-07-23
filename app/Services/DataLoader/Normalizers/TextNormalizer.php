<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use function is_null;
use function preg_replace;
use function str_replace;
use function trim;

class TextNormalizer implements Normalizer {
    public function normalize(mixed $value): ?string {
        if (!is_null($value)) {
            $value = (string) $value;
            $value = preg_replace('/[\h\x00]/ui', ' ', $value);
            $value = str_replace(["\r\n", "\n\r", "\r"], "\n", $value);
            $value = trim($value);
        }

        return $value;
    }
}
