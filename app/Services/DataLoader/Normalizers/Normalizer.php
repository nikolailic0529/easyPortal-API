<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Normalizers;

use JetBrains\PhpStorm\Pure;

interface Normalizer {
    #[Pure]
    public function normalize(mixed $value): string;
}
