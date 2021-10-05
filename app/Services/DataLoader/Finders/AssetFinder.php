<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Asset;

interface AssetFinder {
    public function find(string $key): ?Asset;
}
