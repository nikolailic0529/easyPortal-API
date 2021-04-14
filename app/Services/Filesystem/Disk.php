<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Stringable;

use function sprintf;

abstract class Disk implements Stringable {
    public const NAME = null;

    protected ?Filesystem $fake = null;

    public function __construct(
        protected Application $app,
        protected Factory $factory,
    ) {
        // empty
    }

    public function filesystem(): Filesystem {
        if ($this->app->runningUnitTests()) {
            $this->fake ??= $this->fake();

            return $this->fake;
        }

        return $this->factory->disk(static::NAME);
    }

    protected function fake(): Filesystem {
        if (!$this->app->runningUnitTests()) {
            throw new LogicException(sprintf(
                'Method `%s` can be called only while unit tests.',
                __METHOD__,
            ));
        }

        return Storage::fake(static::NAME);
    }

    public function __toString(): string {
        return static::NAME;
    }
}
