<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Loaders;

use App\Services\DataLoader\Processors\Loader\LoaderState;

class AssetLoaderState extends LoaderState {
    public bool $withWarrantyCheck = false;
}
