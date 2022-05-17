<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use Composer\InstalledVersions;
use Composer\Package\RootPackage;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

use function end;
use function explode;
use function str_starts_with;

class ApplicationInfo {
    public const DEFAULT_VERSION = '0.0.0';

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
        return $this->getPackageInfo()['name'];
    }

    public function getVersion(): string {
        // Cached version?
        $path = $this->getCachedVersionPath();

        if ($path && $this->filesystem->exists($path)) {
            return require $path;
        }

        // Try to find package version through Composer
        $package = $this->getPackageInfo();
        $version = $package['pretty_version'];

        if ($version === RootPackage::DEFAULT_PRETTY_VERSION) {
            // Default? -> unknown
            $version = null;
        } elseif (str_starts_with($version, 'dev-')) {
            // Composer may use the branch as a version (`dev-*`), but I'm not
            // sure how to handle it ... so it is ignored.
            $version = null;
        } else {
            // ok
        }

        return $version ?: self::DEFAULT_VERSION;
    }

    public function getCachedVersionPath(): string {
        return $this->config->get('ep.version.cache')
            ?: $this->app->bootstrapPath('cache/ep-version.php');
    }

    /**
     * @return array{name: string, version: string, pretty_version: string}
     */
    protected function getPackageInfo(): array {
        return InstalledVersions::getRootPackage();
    }
}
