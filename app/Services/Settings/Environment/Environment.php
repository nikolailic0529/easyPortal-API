<?php declare(strict_types = 1);

namespace App\Services\Settings\Environment;

use Dotenv\Repository\RepositoryInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\Env;

class Environment {
    public const SETTING = 'ep.settings.envs';

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
                $this->repository = new EnvironmentRepository((array) $this->config->get(self::SETTING));
            } else {
                $this->repository = Env::getRepository();
            }
        }

        return $this->repository;
    }
}
