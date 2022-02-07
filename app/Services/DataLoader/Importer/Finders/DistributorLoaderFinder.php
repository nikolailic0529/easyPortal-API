<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Finders;

use App\Models\Distributor;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\Finder;
use App\Services\DataLoader\Loader\Loaders\DistributorLoader;

class DistributorLoaderFinder extends Finder implements DistributorFinder {
    public function find(string $key): ?Distributor {
        return $this->container->make(DistributorLoader::class)->create($key);
    }
}
