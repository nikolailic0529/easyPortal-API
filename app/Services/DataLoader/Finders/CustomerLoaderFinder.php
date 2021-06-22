<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Customer;
use App\Services\DataLoader\Loaders\CustomerLoader;

class CustomerLoaderFinder extends Finder implements CustomerFinder {
    public function find(string $key): ?Customer {
        return $this->container->make(CustomerLoader::class)->create($key);
    }
}
