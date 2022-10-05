<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Finders;

use App\Models\Reseller;
use App\Services\DataLoader\Finders\Finder;
use App\Services\DataLoader\Finders\ResellerFinder;
use App\Services\DataLoader\Processors\Loader\Loaders\ResellerLoader;

class ResellerLoaderFinder extends Finder implements ResellerFinder {
    public function find(string $key): ?Reseller {
        $result = $this->container->make(ResellerLoader::class)->setObjectId($key)->start();
        $model  = $result
            ? Reseller::query()->whereKey($key)->first()
            : null;

        return $model;
    }
}
