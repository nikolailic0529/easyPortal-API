<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader;

class CompanyLoaderState extends LoaderState {
    public bool $withAssets    = false;
    public bool $withDocuments = false;
}
