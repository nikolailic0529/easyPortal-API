<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\ApplicationInfo;
use App\Services\Maintenance\Events\VersionUpdated;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'ep:maintenance-version-reset')]
class VersionReset extends Command {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:maintenance-version-reset';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $description = <<<'DESC'
        Resets application version.
        DESC;

    public function __invoke(Dispatcher $dispatcher, Filesystem $filesystem, ApplicationInfo $info): int {
        // Remove
        $previous = $info->getVersion();
        $result   = true;
        $path     = $info->getCachedVersionPath();

        if ($filesystem->exists($path)) {
            $result = $filesystem->delete($path);
        }

        // Dispatch
        if ($result) {
            $dispatcher->dispatch(new VersionUpdated($info->getVersion(), $previous));
        }

        // Done
        if ($result) {
            $this->info('Done.');
        } else {
            $this->error('Failed.');
        }

        // Return
        return $result
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
