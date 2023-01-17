<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Finders;

use App\Models\Asset;
use App\Services\DataLoader\Finders\AssetFinder;
use App\Services\DataLoader\Finders\Finder;
use App\Services\DataLoader\Processors\Loader\Loaders\AssetLoader;

class AssetLoaderFinder extends Finder implements AssetFinder {
    public function __construct(
        protected AssetLoader $loader,
    ) {
        parent::__construct();
    }

    public function find(string $key): ?Asset {
        $result = $this->loader->setObjectId($key)->start();
        $model  = $result
            ? Asset::query()->whereKey($key)->first()
            : null;

        return $model;
    }
}
