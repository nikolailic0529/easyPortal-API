<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Queue\CronJob;

/**
 * Start the maintenance mode (please do not run by hand).
 */
class EnableCronJob extends CronJob {
    public function displayName(): string {
        return 'ep-maintenance-enable';
    }

    public function __invoke(Maintenance $maintenance): void {
        $maintenance->enable();
    }
}
