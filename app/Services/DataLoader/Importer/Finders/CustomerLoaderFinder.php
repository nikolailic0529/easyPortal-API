<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Finders;

use App\Models\Customer;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\Finder;
use App\Services\DataLoader\Loader\Loaders\CustomerLoader;

class CustomerLoaderFinder extends Finder implements CustomerFinder {
    public function find(string $key): ?Customer {
        return $this->container->make(CustomerLoader::class)->create($key);
    }
}
