<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\ApplicationInfo;
use App\Services\Maintenance\Events\VersionUpdated;
use App\Services\Maintenance\Utils\SemanticVersion;
use App\Utils\Console\WithOptions;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

use function ltrim;
use function sprintf;
use function substr;
use function trim;
use function var_export;

#[AsCommand(name: 'ep:maintenance-version-update')]
class VersionUpdate extends Command {
    use WithOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:maintenance-version-update
        {version   : version number (should be valid Semantic Version string; if empty only the build will be updated)}
        {--commit= : commit sha (optional, will be added as metadata)}
        {--build=  : build number (optional, will be added as metadata)}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $description = <<<'DESC'
        Updates application version.
        DESC;

    public function __invoke(Dispatcher $dispatcher, Filesystem $filesystem, ApplicationInfo $info): int {
        // Version
        $previous = $info->getVersion();
        $version  = ltrim(trim((string) $this->getStringArgument('version')), 'v.');
        $version  = new SemanticVersion(($version ?: $previous) ?: ApplicationInfo::DEFAULT_VERSION);
        $commit   = $this->getStringOption('commit') ?: null;
        $build    = $this->getStringOption('build') ?: null;
        $meta     = null;

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
        $path   = $info->getCachedVersionPath();
        $data   = var_export((string) $version, true);
        $result = $filesystem->put($path, /** @lang PHP */ "<?php return {$data};");

        $this->line(sprintf('Updating Version to `%s`...', $version));
        $this->newLine();

        if (!$result) {
            $this->error('Failed.');

            return self::FAILURE;
        }

        // Dispatch
        $dispatcher->dispatch(new VersionUpdated((string) $version, $previous));

        // Done
        $this->info('Done.');

        // Return
        return self::SUCCESS;
    }
}
