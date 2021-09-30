<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Environment\Configuration;
use Config\Constants;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration as IlluminateLoadConfiguration;
use Illuminate\Support\Env;
use ReflectionClass;

use function pathinfo;

use const PATHINFO_FILENAME;

class LoadConfiguration extends IlluminateLoadConfiguration {
    protected function loadConfigurationFiles(Application $app, Repository $repository): void {
        $configuration = $app->make(Configuration::class)->getConfiguration();

        $this->overwriteEnvVars($app, $repository, $configuration['envs']);

        parent::loadConfigurationFiles($app, $repository);

        $this->overwriteConfig($app, $repository, $configuration['config']);

        // If ENV var exists the Setting will be marked as read-only and will
        // be not possible to set its value. To avoid this we reset our ENVs
        // after boot (after boot because app/packages may register additional
        // configs and ENVs is required for them).
        $app->booted(function () use ($app, $repository, $configuration): void {
            $this->cleanupEnvVars($app, $repository, $configuration['envs']);
        });
    }

    /**
     * @return array<string,string>
     */
    protected function getConfigurationFiles(Application $app): array {
        $files     = parent::getConfigurationFiles($app);
        $constants = (new ReflectionClass(Constants::class))->getFileName();

        if ($constants) {
            unset($files[pathinfo($constants, PATHINFO_FILENAME)]);
        }

        return $files;
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
     * @param array<string> $vars
     */
    protected function cleanupEnvVars(Application $app, Repository $repository, array $vars): void {
        $repository = $this->getEnvRepository();

        foreach ($vars as $name => $value) {
            $repository->clear($name);
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
