<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Distributor;
use App\Services\DataLoader\Container\Isolated;

interface DistributorFinder extends Isolated {
    public function find(string $key): ?Distributor;
}
