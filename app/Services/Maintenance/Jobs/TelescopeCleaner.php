<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Queue\CronJob;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

class TelescopeCleaner extends CronJob {
    public function displayName(): string {
        return 'ep-maintenance-telescope-cleaner';
    }

    /**
     * @return array<string, mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'expire' => 'P1M',
                ],
            ] + parent::getQueueConfig();
    }

    public function __invoke(Kernel $kernel, QueueableConfigurator $configurator): void {
        $date   = Date::now();
        $config = $configurator->config($this);
        $expire = $config->setting('expire');
        $hours  = $expire
            ? $date->diffInHours($date->sub($expire))
            : null;

        $kernel->call('telescope:prune', [
            '--hours' => $hours,
        ]);
    }
}
