<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Config;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration as IlluminateLoadConfiguration;
use Illuminate\Support\Env;

class LoadConfiguration extends IlluminateLoadConfiguration {
    protected function loadConfigurationFiles(Application $app, Repository $repository): void {
        $config = $app->make(Config::class);

        $this->loadEnvVars($app, $repository, $config);
        parent::loadConfigurationFiles($app, $repository);
        $this->loadSettings($app, $repository, $config);
    }

    protected function loadEnvVars(Application $app, Repository $repository, Config $config): void {
        $repository = $this->getEnvRepository();

        foreach ($config->getEnvVars() as $name => $value) {
            if (!$repository->has($name)) {
                $repository->set($name, $value);
            }
        }
    }

    protected function loadSettings(Application $app, Repository $repository, Config $config): void {
        foreach ($config->getConfig() as $path => $value) {
            $repository->set($path, $value);
        }
    }

    protected function getEnvRepository(): RepositoryInterface {
        return Env::getRepository();
    }
}
