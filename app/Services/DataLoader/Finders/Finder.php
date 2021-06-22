<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Services\DataLoader\Container\Container;

abstract class Finder {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }
}
