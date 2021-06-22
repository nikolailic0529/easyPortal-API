<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Reseller;
use App\Services\DataLoader\Container\Isolated;

interface ResellerFinder extends Isolated {
    public function find(string $key): ?Reseller;
}
