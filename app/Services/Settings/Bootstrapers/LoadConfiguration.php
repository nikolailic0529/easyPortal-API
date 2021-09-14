<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Environment\Configuration;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration as IlluminateLoadConfiguration;
use Illuminate\Support\Env;

class LoadConfiguration extends IlluminateLoadConfiguration {
    protected function loadConfigurationFiles(Application $app, Repository $repository): void {
        $configuration = $app->make(Configuration::class)->getConfiguration();

        $this->overwriteEnvVars($app, $repository, $configuration['envs']);

        parent::loadConfigurationFiles($app, $repository);

        $this->overwriteConfig($app, $repository, $configuration['config']);
    }

    /**
     * @param array<string,string> $vars
     */
    protected function overwriteEnvVars(Application $app, Repository $repository, array $vars): void {
        $repository = $this->getEnvRepository();

        foreach ($vars as $name => $value) {
            if (!$repository->has($name)) {
                $repository->set($name, $value);
            }
        }
    }

    /**
     * @param array<string,mixed> $config
     */
    protected function overwriteConfig(Application $app, Repository $repository, array $config): void {
        foreach ($config as $path => $value) {
            $repository->set($path, $value);
        }
    }

    protected function getEnvRepository(): RepositoryInterface {
        return Env::getRepository();
    }
}
