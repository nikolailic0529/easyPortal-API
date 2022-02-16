<?php declare(strict_types = 1);

namespace App\Services\App;

use App\Services\Service as BaseService;
use Composer\InstalledVersions;

class Service extends BaseService {
    public function getVersion(): ?string {
        $package = InstalledVersions::getRootPackage();
        $version = $package['pretty_version'] ?? null;

        return $version;
    }
}
