<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use Composer\InstalledVersions;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

use function end;
use function explode;
use function str_starts_with;

class ApplicationInfo {
    public function __construct(
        protected Application $app,
        protected Repository $config,
        protected Filesystem $filesystem,
    ) {
        // empty
    }

    public function getName(): string {
        $parts = explode('/', $this->getPackage());
        $name  = end($parts);

        return $name;
    }

    public function getPackage(): string {
        return InstalledVersions::getRootPackage()['name'] ?? 'easyportal-api';
    }

    public function getVersion(): ?string {
        // Cached version?
        $path = $this->getCachedVersionPath();

        if ($this->filesystem->exists($path)) {
            return require $path;
        }

        // Try to find package version through Composer
        $package = InstalledVersions::getRootPackage();
        $version = $package['pretty_version'] ?? null;

        if ($version !== null && str_starts_with($version, 'dev-')) {
            // Composer may use the branch as a version (`dev-*`), but I'm not
            // sure how to handle it ... so it is ignored.
            $version = '0.0.0';
        }

        return $version;
    }

    public function getCachedVersionPath(): string {
        return $this->config->get('ep.version.cache')
            ?: $this->app->bootstrapPath('cache/ep-version.php');
    }
}
