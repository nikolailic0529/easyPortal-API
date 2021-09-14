<?php declare(strict_types = 1);

namespace App\Services\Settings;

use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Foundation\Application as ApplicationImpl;
use Illuminate\Support\Env;

use function basename;
use function dirname;
use function is_file;

class Environment {
    protected RepositoryInterface $repository;

    public function __construct(
        protected Application $app,
        protected Repository $config,
    ) {
        // empty
    }

    public function has(string $name): bool {
        return $this->getRepository()->has($name);
    }

    public function get(string $name): mixed {
        return $this->getRepository()->get($name);
    }

    protected function getRepository(): RepositoryInterface {
        if (!isset($this->repository)) {
            if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
                // If config is cached we need to load the ENV file that was
                // used for cache, we are trying to use the stored value
                // from the config or search for the most suitable file.
                $default = ".{$this->app->environment()}";
                $files   = [
                    $this->config->get(Settings::ENV_PATH),
                ];

                if ($this->app instanceof ApplicationImpl) {
                    $files[] = "{$this->app->environmentPath()}/{$default}";
                    $files[] = $this->app->environmentFilePath();
                } else {
                    $files[] = $this->app->basePath($default);
                }

                foreach ($files as $file) {
                    if ($file && is_file($file)) {
                        $this->repository = RepositoryBuilder::createWithDefaultAdapters()->immutable()->make();

                        Dotenv::create($this->repository, dirname($file), basename($file))->safeLoad();
                        break;
                    }
                }
            } else {
                $this->repository = Env::getRepository();
            }
        }

        return $this->repository;
    }
}
