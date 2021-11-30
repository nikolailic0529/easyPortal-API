<?php declare(strict_types = 1);

namespace App\Services\Maintenance\Jobs;

use App\Services\Maintenance\Maintenance;
use App\Services\Queue\CronJob;

/**
 * Disable the maintenance mode.
 */
class DisableCronJob extends CronJob {
    public function displayName(): string {
        return 'ep-maintenance-disable';
    }

    public function __invoke(Maintenance $maintenance): void {
        $maintenance->disable();
    }
}
