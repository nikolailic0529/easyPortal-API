<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Customer;
use App\Services\DataLoader\Container\Isolated;

interface CustomerFinder extends Isolated {
    public function find(string $key): ?Customer;
}
