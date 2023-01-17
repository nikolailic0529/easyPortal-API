<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Processors\Finders;

use App\Models\Customer;
use App\Services\DataLoader\Finders\CustomerFinder;
use App\Services\DataLoader\Finders\Finder;
use App\Services\DataLoader\Processors\Loader\Loaders\CustomerLoader;

class CustomerLoaderFinder extends Finder implements CustomerFinder {
    public function __construct(
        protected CustomerLoader $loader,
    ) {
        parent::__construct();
    }

    public function find(string $key): ?Customer {
        $result = $this->loader->setObjectId($key)->start();
        $model  = $result
            ? Customer::query()->whereKey($key)->first()
            : null;

        return $model;
    }
}
