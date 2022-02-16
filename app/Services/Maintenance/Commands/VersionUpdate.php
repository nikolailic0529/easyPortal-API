<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\ApplicationInfo;
use App\Services\Maintenance\Events\VersionUpdated;
use App\Services\Maintenance\Utils\Composer;
use App\Services\Maintenance\Utils\SemanticVersion;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

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
    protected $signature = 'ep:maintenance-version-update
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

    public function __invoke(Dispatcher $dispatcher, ApplicationInfo $info, Composer $composer): int {
        // Version
        $current = $info->getVersion();
        $version = ltrim(trim((string) $this->argument('version')), 'v.');
        $version = new SemanticVersion(($version ?: $current) ?? '0.0.0');
        $commit  = $this->option('commit') ?: null;
        $build   = $this->option('build') ?: null;
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

        $result = $composer->setVersion((string) $version, function (string $stderr): void {
            $output = $this->getOutput()->getOutput();

            if ($output instanceof ConsoleOutputInterface) {
                $output->getErrorOutput()->writeln($stderr);
            } else {
                $this->error($stderr);
            }
        });

        if ($result !== self::SUCCESS) {
            $this->error('Failed.');

            return $result;
        }

        // Dispatch
        $dispatcher->dispatch(new VersionUpdated((string) $version, $current));

        // Done
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }
}
