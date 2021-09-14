<?php declare(strict_types = 1);

namespace App\Services\Settings;

use App\Services\Settings\Exceptions\FailedToLoadEnv;
use Dotenv\Dotenv;
use Exception;
use Illuminate\Contracts\Foundation\Application;

use function array_key_exists;
use function file_get_contents;

class Environment {
    /**
     * @var array<string,mixed>
     */
    protected array $loaded;

    public function __construct(
        protected Application $app,
    ) {
        // empty
    }

    public function has(string $name): bool {
        return array_key_exists($name, $this->load());
    }

    public function get(string $name): mixed {
        return $this->load()[$name] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function load(): array {
        if (!isset($this->loaded)) {
            $path         = $this->getPath();
            $this->loaded = [];

            try {
                $this->loaded = Dotenv::parse(file_get_contents($path));
            } catch (Exception $exception) {
                throw new FailedToLoadEnv($path, $exception);
            }
        }

        return $this->loaded;
    }

    protected function getPath(): string {
        return "{$this->app->environmentPath()}/{$this->app->environmentFile()}";
    }
}
