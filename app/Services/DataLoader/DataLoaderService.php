<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Container\Container;

class DataLoaderService {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public function make(string $class): object {
        return $this->container->make($class);
    }

    public function getClient(): Client {
        return $this->container->make(Client::class);
    }
}
