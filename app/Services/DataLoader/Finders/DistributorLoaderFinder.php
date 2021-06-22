<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Distributor;
use App\Services\DataLoader\Loaders\DistributorLoader;

class DistributorLoaderFinder extends Finder implements DistributorFinder {
    public function find(string $key): ?Distributor {
        return $this->container->make(DistributorLoader::class)->create($key);
    }
}
