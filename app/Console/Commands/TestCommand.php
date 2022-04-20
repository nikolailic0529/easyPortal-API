<?php declare(strict_types = 1);

namespace App\Console\Commands;

use NunoMaduro\Collision\Adapters\Laravel\Commands\TestCommand as CollisionTestCommand;

use function array_merge;

class TestCommand extends CollisionTestCommand {
    /**
     * @var array<mixed>
     */
    private array $paratestEnvVariables = [];

    public function handle(): mixed {
        // GraphQL Schema generation is expensive and can take up to ~80% of
        // time while running tests. To speed up tests we enable the Lighthouse
        // cache and generate the Schema only one time before all tests.
        $isParallel = (bool) $this->option('parallel');

        if ($isParallel) {
            $this->call('lighthouse:cache');
            $this->setParatestEnvVariables([
                'LIGHTHOUSE_CACHE_ENABLE'  => true,
                'LIGHTHOUSE_CACHE_VERSION' => 2,
            ]);
        }

        // Run
        try {
            return parent::handle();
        } finally {
            if ($isParallel) {
                $this->call('lighthouse:clear-cache');
            }
        }
    }

    /**
     * @param array<mixed> $variables
     */
    protected function setParatestEnvVariables(array $variables): void {
        $this->paratestEnvVariables = $variables;
    }

    /**
     * @return array<mixed>
     */
    protected function paratestEnvironmentVariables(): array {
        return array_merge(parent::paratestEnvironmentVariables(), $this->paratestEnvVariables);
    }
}
