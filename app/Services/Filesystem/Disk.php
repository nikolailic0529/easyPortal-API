<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Filesystem as FlysystemFilesystem;
use LogicException;
use Stringable;

use function sprintf;

abstract class Disk implements Stringable {
    public const NAME = null;

    public function __construct(
        protected Factory $factory,
    ) {
        // empty
    }

    public function getName(): string {
        return static::NAME;
    }

    public function isPublic(): bool {
        $fs     = $this->filesystem();
        $driver = $fs instanceof FilesystemAdapter
            ? $fs->getDriver()
            : null;
        $config = $driver instanceof FlysystemFilesystem
            ? $driver->getConfig()
            : null;
        $public = $config?->get('visibility') === AdapterInterface::VISIBILITY_PUBLIC;

        return $public;
    }

    public function filesystem(): Filesystem {
        return $this->factory->disk($this->getName());
    }

    public function __toString(): string {
        return $this->getName();
    }

    public function url(string $path): string {
        // Public?
        if (!$this->isPublic()) {
            throw new LogicException(sprintf(
                'Is not possible to get url for the file from non-public disk `%s`.',
                $this->getName(),
            ));
        }

        // Get
        $fs  = $this->filesystem();
        $url = $fs instanceof Cloud
            ? $fs->url($path)
            : null;

        if (!$url) {
            throw new LogicException(sprintf(
                'Is not possible to get url for the file because disk `%s` doesn\'t support this.',
                $this->getName(),
            ));
        }

        // Return
        return $url;
    }
}
