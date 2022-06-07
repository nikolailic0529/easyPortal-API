<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer\Normalizers;

use Illuminate\Support\Str;

use function preg_replace;
use function str_replace;

class NameNormalizer extends StringNormalizer {
    public function normalize(mixed $value): ?string {
        $value = parent::normalize($value);

        if ($value !== null) {
            $value = str_replace('_', ' ', $value);
            $value = preg_replace('/(\p{Ll})(\p{Lu})/u', '$1 $2', $value) ?: $value;
            $value = Str::title($value);
        }

        return $value;
    }
}
