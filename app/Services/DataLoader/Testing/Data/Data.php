<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use App\Services\DataLoader\Normalizer;
use Closure;
use Faker\Generator;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;

abstract class Data {
    public function __construct(
        protected Kernel $kernel,
        protected Application $app,
        protected Repository $config,
        protected Generator $faker,
        protected Normalizer $normalizer,
    ) {
        // empty
    }

    /**
     * @return bool|array<string,mixed>
     */
    abstract public function generate(string $path): bool|array;

    /**
     * @param array<string,mixed> $context
     */
    public function restore(string $path, array $context): bool {
        return true;
    }

    protected function dumpClientResponses(string $path, Closure $closure): bool {
        $previous = $this->config->get('ep.data_loader.dump');

        $this->config->set('ep.data_loader.dump', $path);

        try {
            return $closure($path) && $this->cleanClientDumps($path);
        } finally {
            $this->config->set('ep.data_loader.dump', $previous);
        }
    }

    protected function cleanClientDumps(string $path): bool {
        $cleaner = $this->app->make(ClientDataCleaner::class);

        foreach ((new ClientDumpsIterator($path))->getResponseIterator(true) as $object) {
            $cleaner->clean($object);
        }

        return true;
    }
}