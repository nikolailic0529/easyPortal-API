<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizer;

interface ValueNormalizer {
    public function normalize(mixed $value): mixed;
}
