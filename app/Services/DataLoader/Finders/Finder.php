<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Finders;

use App\Services\DataLoader\Container\Isolated;
use App\Services\DataLoader\Container\Singleton;

abstract class Finder implements Isolated, Singleton {
    public function __construct() {
        // empty
    }
}
