<?php declare(strict_types = 1);

namespace App\Services;

use App\Disc;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;

class Filesystem {
    public function __construct(
        protected Factory $factory,
    ) {
        // empty
    }

    public function disk(Disc $disc): FilesystemContract {
        return $this->factory->disk($disc->getValue());
    }
}
