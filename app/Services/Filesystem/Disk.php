<?php declare(strict_types = 1);

namespace App\Services\Filesystem;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use LogicException;
use Stringable;
use Symfony\Component\HttpFoundation\Response;

use function sprintf;

abstract class Disk implements Stringable {
    public const NAME = null;

    public function __construct(
        protected Factory $factory,
        protected Repository $config,
    ) {
        // empty
    }

    public function getName(): string {
        return static::NAME;
    }

    public function isPublic(): bool {
        $visibility = $this->config->get("filesystems.disks.{$this->getName()}.visibility");
        $public     = $visibility === FilesystemAdapter::VISIBILITY_PUBLIC;

        return $public;
    }

    public function filesystem(): Filesystem {
        return $this->factory->disk($this->getName());
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function download(string $path, string $name = null, array $headers = []): Response {
        // Create
        $fs       = $this->filesystem();
        $response = null;

        if ($fs instanceof FilesystemAdapter) {
            $response = $fs->download($path, $name, $headers);
        } else {
            // Theoretically, $fs may implement `\Illuminate\Contracts\Filesystem\Filesystem`
            // only. In this case we should use `$fs->readStream($path)` and
            // create response by hand

            throw new LogicException('Not implemented.');
        }

        // Return
        return $response;
    }

    public function __toString(): string {
        return $this->getName();
    }

    public function url(string $path): string {
        // Public?
        if (!$this->isPublic()) {
            throw new LogicException(sprintf(
                'It is not possible to get url for the file from non-public disk `%s`.',
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
