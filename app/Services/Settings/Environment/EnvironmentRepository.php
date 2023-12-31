<?php declare(strict_types = 1);

namespace App\Services\Settings\Environment;

use Dotenv\Repository\RepositoryInterface;

use function array_key_exists;

class EnvironmentRepository implements RepositoryInterface {
    /**
     * @param array<string,mixed> $vars
     */
    public function __construct(
        protected array $vars = [],
    ) {
        // empty
    }

    /**
     * @return array<string,mixed>
     */
    public function getVars(): array {
        return $this->vars;
    }

    public function has(string $name): bool {
        return array_key_exists($name, $this->vars);
    }

    public function get(string $name): mixed {
        return $this->vars[$name] ?? null;
    }

    public function set(string $name, string $value): bool {
        $this->vars[$name] = $value;

        return true;
    }

    public function clear(string $name): bool {
        unset($this->vars[$name]);

        return true;
    }
}
