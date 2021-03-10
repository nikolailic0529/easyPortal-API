<?php declare(strict_types = 1);

namespace App\Services\DataLoader;

use App\Services\DataLoader\Container\Container;

class DataLoaderService {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }

    /**
     * @param class-string<\App\Services\DataLoader\Loader> $loader
     */
    public function make(string $loader): Loader {
        return $this->container->make($loader);
    }
}
