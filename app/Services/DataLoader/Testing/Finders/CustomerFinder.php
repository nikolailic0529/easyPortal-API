<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Finders;

use App\Models\Customer;
use App\Services\DataLoader\Finders\CustomerFinder as CustomerFinderContract;

class CustomerFinder implements CustomerFinderContract {
    public function find(string $key): ?Customer {
        return Customer::factory()->create([
            'id' => $key,
        ]);
    }
}
