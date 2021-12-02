<?php declare(strict_types = 1);

namespace App\Services\Queue\Jobs;

use App\Services\Queue\CronJob;
use Illuminate\Contracts\Console\Kernel;

/**
 * Creates Horizon snapshot.
 */
class SnapshotCronJob extends CronJob {
    public function displayName(): string {
        return 'ep-queue-snapshot';
    }

    public function __invoke(Kernel $artisan): void {
        $artisan->call('horizon:snapshot');
    }
}
