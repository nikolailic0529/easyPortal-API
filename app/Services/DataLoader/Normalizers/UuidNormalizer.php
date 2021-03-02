<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use function strtolower;

/**
 * @internal
 */
class UuidNormalizer extends StringNormalizer {
    public function normalize(mixed $value): string {
        $value = parent::normalize($value);
        $value = strtolower($value);

        return $value;
    }
}
