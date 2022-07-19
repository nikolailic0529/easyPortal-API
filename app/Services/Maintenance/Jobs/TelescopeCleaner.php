<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Queue\CronJob;
use Exception;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Date;
use Laravel\Telescope\Contracts\EntriesRepository;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use PDOException;

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

    public function __invoke(Kernel $kernel, QueueableConfigurator $configurator, EntriesRepository $repository): void {
        if (!$this->isEnabled($repository)) {
            return;
        }

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

    protected function isEnabled(EntriesRepository $repository): bool {
        // Table may not exist eg if the migrate command was run when
        // `TELESCOPE_ENABLED` was `false`. The job will fail in this case, so
        // we need to check that table exists before prune.
        //
        // https://github.com/fakharanwar/easyPortal-API/issues/944
        try {
            $repository->find('');
        } catch (PDOException) {
            return false;
        } catch (Exception) {
            // ok
        }

        return true;
    }
}
