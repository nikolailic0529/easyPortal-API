<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Config;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration as IlluminateLoadConfiguration;

class LoadConfiguration extends IlluminateLoadConfiguration {
    protected function loadConfigurationFiles(Application $app, Repository $repository): void {
        parent::loadConfigurationFiles($app, $repository);
        $this->loadSettings($app, $repository);
    }

    protected function loadSettings(Application $app, Repository $repository): void {
        foreach ($app->make(Config::class)->getConfig() as $name => $value) {
            $repository->set($name, $value);
        }
    }
}
