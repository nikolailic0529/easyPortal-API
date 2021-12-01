<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\Maintenance;
use App\Utils\Console\WithBooleanOptions;
use App\Utils\Console\WithWait;
use Illuminate\Console\Command;

class Stop extends Command {
    use WithWait;
    use WithBooleanOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:data-maintenance-stop
        {--wait : wait until maintenance is really stopped}
        {--no-wait : do not wait (default)}
        {--wait-timeout=60 : wait timeout (seconds)}
        {--force : force stop}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Stop the maintenance';

    public function handle(Maintenance $maintenance): int {
        // Start
        $force  = $this->getBooleanOption('force', false);
        $result = $maintenance->stop($force);

        // Wait?
        if ($result && !$force) {
            $wait    = $this->getBooleanOption('wait', false);
            $timeout = (int) $this->option('wait-timeout');

            if ($wait && $timeout > 0) {
                $result = $this->wait($timeout, static function () use ($maintenance): bool {
                    return $maintenance->getSettings() === null;
                });
            }
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
