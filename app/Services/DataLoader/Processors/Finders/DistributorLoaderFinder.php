<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Finders;

use App\Models\Distributor;
use App\Services\DataLoader\Finders\DistributorFinder;
use App\Services\DataLoader\Finders\Finder;
use App\Services\DataLoader\Processors\Loader\Loaders\DistributorLoader;

class DistributorLoaderFinder extends Finder implements DistributorFinder {
    public function find(string $key): ?Distributor {
        $result = $this->container->make(DistributorLoader::class)->setObjectId($key)->start();
        $model  = $result
            ? Distributor::query()->whereKey($key)->first()
            : null;

        return $model;
    }
}
