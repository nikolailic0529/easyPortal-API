<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\Maintenance;
use App\Utils\Console\WithOptions;
use App\Utils\Console\WithWait;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'ep:maintenance-start')]
class Start extends Command {
    use WithWait;
    use WithOptions;

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $signature = 'ep:maintenance-start
        {--duration=1 hour : maintenance duration}
        {--message= : message}
        {--wait : wait until maintenance is really started (default)}
        {--no-wait : do not wait}
        {--wait-timeout=60 : wait timeout (seconds)}
        {--force : force start}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string|null
     */
    protected $description = 'Start the maintenance';

    public function handle(Maintenance $maintenance): int {
        // Start
        $duration = CarbonInterval::make((string) $this->getStringOption('duration'));
        $message  = $this->getStringOption('message');
        $end      = Date::now()->add($duration);
        $force    = $this->getBoolOption('force', false);
        $result   = $maintenance->start($end, $message, $force);

        // Wait?
        if ($result && !$force) {
            $wait    = $this->getBoolOption('wait', true);
            $timeout = $this->getIntOption('wait-timeout');

            if ($wait && $timeout > 0) {
                $result = $this->wait($timeout, static function () use ($maintenance): bool {
                    return $maintenance->isEnabled();
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
