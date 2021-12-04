<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Maintenance\Notifications\Completed;
use App\Services\Queue\CronJob;
use Illuminate\Contracts\Config\Repository;

/**
 * Complete the maintenance mode (please do not run by hand).
 */
class CompleteCronJob extends CronJob {
    use NotifyUsers;

    public function displayName(): string {
        return 'ep-maintenance-complete';
    }

    public function __invoke(Repository $config, Maintenance $maintenance): void {
        $settings = $maintenance->getSettings();

        $maintenance->disable();

        if ($settings?->notified) {
            $this->notify($config, new Completed());
        }
    }
}
