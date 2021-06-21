<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Customer;

interface CustomerFinder {
    public function find(string $key): ?Customer;
}
