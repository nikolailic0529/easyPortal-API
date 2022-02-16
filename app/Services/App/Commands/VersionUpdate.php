<?php declare(strict_types = 1);

namespace App\Services\App\Commands;

use App\Services\App\Events\VersionUpdated;
use App\Services\App\Service;
use App\Services\App\Utils\Composer;
use App\Services\App\Utils\SemanticVersion;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;

use function ltrim;
use function sprintf;
use function substr;
use function trim;

class VersionUpdate extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:app-version-update
        {version  : version number (should be valid Semantic Version string; if empty only the build will be updated)}
        {--commit= : commit sha (optional, will be added as metadata)}
        {--build= : build number (optional, will be added as metadata)}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = <<<'DESC'
        Updates application version in `composer.json` (should be called before `composer install`).
        DESC;

    public function __invoke(Dispatcher $dispatcher, Service $service, Composer $composer): int {
        // Version
        $current = $service->getVersion();
        $version = ltrim(trim((string) $this->argument('version')), 'v.');
        $version = new SemanticVersion(($version ?: $current) ?? '0.0.0');
        $commit  = $this->option('commit');
        $build   = $this->option('build');
        $meta    = null;

        if ($commit !== null) {
            $commit = substr($commit, 0, 7);
        }

        if ($commit !== null && $build !== null) {
            $meta = "{$commit}.{$build}";
        } elseif ($commit !== null) {
            $meta = $commit;
        } elseif ($build !== null) {
            $meta = $build;
        } else {
            // empty
        }

        if ($meta) {
            $version = $version->setMetadata($meta);
        }

        // Update
        $this->line(sprintf('Updating version to `%s`...', $version));
        $this->newLine();

        $result = $composer->setVersion((string) $version);

        if ($result !== self::SUCCESS) {
            $this->error('Failed.');
        }

        // Dispatch
        $dispatcher->dispatch(new VersionUpdated((string) $version, $current));

        // Done
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }
}
