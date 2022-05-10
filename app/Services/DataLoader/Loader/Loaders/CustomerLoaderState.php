<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Loader\Loaders;

use App\Services\DataLoader\Loader\CompanyLoaderState;

class CustomerLoaderState extends CompanyLoaderState {
    public bool $withWarrantyCheck = false;
}
