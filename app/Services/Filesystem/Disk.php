<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Stringable;

abstract class Disk implements Stringable {
    public const NAME = null;

    public function __construct(
        protected Application $app,
        protected Factory $factory,
    ) {
        // empty
    }

    public function getName(): string {
        return static::NAME;
    }

    public function filesystem(): Filesystem {
        return $this->factory->disk($this->getName());
    }

    public function __toString(): string {
        return $this->getName();
    }
}
