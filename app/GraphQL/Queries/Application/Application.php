<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Application;

use Composer\InstalledVersions;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application as MainApp;

use function file_get_contents;
use function json_decode;

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
        $package  = json_decode(file_get_contents($this->app->basePath('composer.json')), true)['name'];
        $version  = InstalledVersions::getVersion($package);
        $response = [
            'name'    => $name,
            'version' => $version,
        ];

        return $response;
    }
}
