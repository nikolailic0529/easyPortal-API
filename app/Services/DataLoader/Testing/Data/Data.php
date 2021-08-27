<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Testing\Data;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use function file_get_contents;
use function json_decode;
use function json_encode;

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

abstract class Data {
    public function __construct(
        protected Kernel $kernel,
        protected Application $app,
        protected Repository $config,
    ) {
        // empty
    }

    abstract public function generate(string $path): bool;

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
        $fs      = new Filesystem();
        $files   = (new Finder())->in($path)->notName(Generator::MARKER)->name('*.json')->files();
        $cleaner = $this->app->make(Cleaner::class);

        foreach ($files as $file) {
            $dump = json_decode(file_get_contents($file->getPathname()), true);
            $dump = $cleaner->clean($dump);
            $dump = json_encode($dump, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $fs->dumpFile($file->getPathname(), $dump);
        }

        return true;
    }
}
