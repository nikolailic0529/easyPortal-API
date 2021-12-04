<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Queue\CronJob;

/**
 * Start the maintenance mode (please do not run by hand).
 */
class StartCronJob extends CronJob {
    public function displayName(): string {
        return 'ep-maintenance-start';
    }

    public function __invoke(Maintenance $maintenance): void {
        if ($maintenance->getSettings()) {
            $maintenance->enable();
        }
    }
}
