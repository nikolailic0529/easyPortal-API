<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Distributor;

interface DistributorFinder {
    public function find(string $key): ?Distributor;
}
