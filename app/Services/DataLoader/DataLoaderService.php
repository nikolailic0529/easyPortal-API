<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loaders\CustomerLoader;

class DataLoaderService {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    public function getCustomerLoader(): CustomerLoader {
        return $this->container->make(CustomerLoader::class);
    }

    public function getClient(): Client {
        return $this->container->make(Client::class);
    }
}
