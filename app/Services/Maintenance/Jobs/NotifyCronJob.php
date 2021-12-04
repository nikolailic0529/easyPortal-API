<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Maintenance\Notifications\Scheduled;
use App\Services\Queue\CronJob;
use Illuminate\Contracts\Config\Repository;

/**
 * Send notifications about scheduled maintenance (please do not run by hand).
 */
class NotifyCronJob extends CronJob {
    use NotifyUsers;

    public function displayName(): string {
        return 'ep-maintenance-notify';
    }

    public function __invoke(Repository $config, Maintenance $maintenance): void {
        $settings = $maintenance->getSettings();

        if ($settings) {
            $maintenance->markAsNotified();
            $this->notify($config, new Scheduled($settings));
        }
    }
}
