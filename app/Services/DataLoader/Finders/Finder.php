<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Services\DataLoader\Container\Container;
use App\Services\DataLoader\Container\Isolated;

abstract class Finder implements Isolated {
    public function __construct(
        protected Container $container,
    ) {
        // empty
    }
}
