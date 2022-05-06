<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Loader\LoaderState;

class AssetLoaderState extends LoaderState {
    public bool $withDocuments = false;
}
