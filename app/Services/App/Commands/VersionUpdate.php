<?php declare(strict_types = 1);

namespace App\Services\App\Commands;

use App\Services\App\Events\VersionUpdated;
use App\Services\App\Service;
use App\Services\App\Utils\Composer;
use App\Utils\SemanticVersion;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;

use function ltrim;
use function sprintf;
use function str_starts_with;
use function trim;

class VersionUpdate extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:app-version-update
        {version  : version number (should be valid Semantic Version string; if empty only the build will be updated)}
        {--build= : build number (optional, will be added into version)}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = <<<'DESC'
        Updates application version in `composer.json` (should be called before `composer install`).
        DESC;

    public function __invoke(Dispatcher $dispatcher, Service $service, Composer $composer): int {
        // Current version
        //
        // Composer may use the branch as a version (`dev-*`), but I'm not sure
        // how to handle it ... so it is ignored.
        $current = $service->getVersion();
        $prefix  = 'dev-';

        if (!$current || str_starts_with($current, $prefix)) {
            $current = '0.0.0';
        }

        // Determine version
        $version = ltrim(trim((string) $this->argument('version')), 'v.');
        $version = new SemanticVersion($version ?: (string) $current);

        if ($this->hasOption('build')) {
            $version = $version->setMetadata($this->option('build'));
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
