<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Oem;

interface OemFinder {
    public function find(string $key): ?Oem;
}
