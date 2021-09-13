<?php declare(strict_types = 1);

namespace App\Services\Settings\Bootstrapers;

use App\Services\Settings\Exceptions\FailedToLoadConfig;
use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryInterface;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables as IlluminateLoadEnvironmentVariables;
use Illuminate\Support\Env;

use function file_get_contents;
use function is_file;

class LoadEnvironmentVariables extends IlluminateLoadEnvironmentVariables {
    public const PATH = 'app/settings.env';

    public function bootstrap(Application $app): void {
        // Parent (load .env)
        parent::bootstrap($app);

        // Load custom settings
        if (!$app->configurationIsCached()) {
            $this->loadSettings($app);
        }
    }

    protected function loadSettings(Application $app): void {
        $path = $this->getSettingsPath($app);

        try {
            $context    = is_file($path) ? file_get_contents($path) : null;
            $variables  = $context ? Dotenv::parse($context) : [];
            $repository = $this->getEnvRepository();

            foreach ($variables as $name => $value) {
                if (!$repository->has($name)) {
                    $repository->set($name, $value);
                }
            }
        } catch (Exception $exception) {
            throw new FailedToLoadConfig($path, $exception);
        }
    }

    protected function getSettingsPath(Application $app): string {
        return $app->storagePath().'/'.static::PATH;
    }

    protected function getEnvRepository(): RepositoryInterface {
        return Env::getRepository();
    }
}
