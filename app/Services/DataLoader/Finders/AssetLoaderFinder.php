<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Models\Asset;
use App\Services\DataLoader\Loaders\AssetLoader;

class AssetLoaderFinder extends Finder implements AssetFinder {
    public function find(string $key): ?Asset {
        return $this->container->make(AssetLoader::class)->create($key);
    }
}
