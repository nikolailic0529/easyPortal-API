<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Importer\Finders;

use App\Models\Asset;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\Finder;
use App\Services\DataLoader\Loader\Loaders\AssetLoader;

class AssetLoaderFinder extends Finder implements AssetFinder {
    public function find(string $key): ?Asset {
        return $this->container->make(AssetLoader::class)
            ->setWithWarrantyCheck(false)
            ->setWithDocuments(false)
            ->create($key);
    }
}
