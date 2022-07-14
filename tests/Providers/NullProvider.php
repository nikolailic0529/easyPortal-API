<?php declare(strict_types = 1);

namespace Tests\Providers;

class NullProvider {
    public function __construct() {
        // empty
    }

    public function __invoke(): mixed {
        return null;
    }
}
