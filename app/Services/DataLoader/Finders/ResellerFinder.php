<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Reseller;

interface ResellerFinder {
    public function find(string $key): ?Reseller;
}
