<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Commands;

use App\Services\Maintenance\Maintenance;
use App\Utils\Console\WithBooleanOptions;
use App\Utils\Console\WithWait;
use Carbon\CarbonInterval;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

class Start extends Command {
    use WithWait;
    use WithBooleanOptions;

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
        {--wait-timeout=60 : wait timeout (seconds)}';

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var string
     */
    protected $description = 'Start the maintenance';

    public function handle(Maintenance $maintenance): int {
        // Start
        $duration = CarbonInterval::make($this->option('duration'));
        $message  = $this->option('message');
        $end      = Date::now()->add($duration);
        $result   = $maintenance->start($end, $message);

        // Wait?
        if ($result) {
            $wait    = $this->getBooleanOption('wait', true);
            $timeout = (int) $this->option('wait-timeout');

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
