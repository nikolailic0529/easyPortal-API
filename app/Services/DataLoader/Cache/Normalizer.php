<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Cache;

use App\Services\DataLoader\Normalizers\KeyNormalizer;

use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_LINE_TERMINATORS;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class Normalizer extends KeyNormalizer {
    public function normalize(mixed $value): string {
        $value = parent::normalize($value);
        $value = (string) json_encode(
            $value,
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_LINE_TERMINATORS,
        );

        return $value;
    }
}
