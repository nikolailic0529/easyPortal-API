<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use Composer\InstalledVersions;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application as MainApp;

class Application {
    public function __construct(
        protected MainApp $app,
        protected Repository $config,
    ) {
        // empty
    }

    /**
     * @return array<string,mixed>
     */
    public function __invoke(): array {
        $name     = $this->config->get('app.name');
        $version  = InstalledVersions::getRootPackage()['version'] ?? null;
        $response = [
            'name'    => $name,
            'version' => $version,
        ];

        return $response;
    }
}
