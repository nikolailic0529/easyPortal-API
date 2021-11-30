<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Queue\CronJob;

/**
 * Start the maintenance mode (internal, please do not touch).
 */
class EnableCronJob extends CronJob {
    public function displayName(): string {
        return 'ep-maintenance-enable';
    }

    public function __invoke(Maintenance $maintenance): void {
        $maintenance->enable();
    }
}
