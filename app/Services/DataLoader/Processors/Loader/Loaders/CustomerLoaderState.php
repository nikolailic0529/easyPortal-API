<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Loader\Loaders;

use App\Services\DataLoader\Processors\Loader\CompanyLoaderState;

class CustomerLoaderState extends CompanyLoaderState {
    public bool $withWarrantyCheck = false;
}
