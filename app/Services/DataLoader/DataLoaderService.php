<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Loaders\AssetLoader;
use App\Services\DataLoader\Loaders\CustomerLoader;
use App\Services\DataLoader\Loaders\DistributorLoader;
use App\Services\DataLoader\Loaders\ResellerLoader;

class DataLoaderService {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    public function getDistributorLoader(): DistributorLoader {
        return $this->container->make(DistributorLoader::class);
    }

    public function getResellerLoader(): ResellerLoader {
        return $this->container->make(ResellerLoader::class);
    }

    public function getCustomerLoader(): CustomerLoader {
        return $this->container->make(CustomerLoader::class);
    }

    public function getAssetLoader(): AssetLoader {
        return $this->container->make(AssetLoader::class);
    }

    public function getClient(): Client {
        return $this->container->make(Client::class);
    }

    public function getContainer(): Container {
        return $this->container;
    }
}
