<?php declare(strict_types = 1);

namespace App\Services\App;

use App\Services\Service as BaseService;
use Composer\InstalledVersions;

use function str_starts_with;

class Service extends BaseService {
    public function getVersion(): ?string {
        $package = InstalledVersions::getRootPackage();
        $version = $package['pretty_version'] ?? null;

        if ($version !== null && str_starts_with($version, 'dev-')) {
            // Composer may use the branch as a version (`dev-*`), but I'm not
            // sure how to handle it ... so it is ignored.
            $version = '0.0.0';
        }

        return $version;
    }
}
