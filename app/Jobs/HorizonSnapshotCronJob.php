<?php declare(strict_types = 1);

namespace App\Jobs;

use App\Services\Queue\CronJob;
use Illuminate\Contracts\Console\Kernel;

/**
 * Creates Horizon snapshot.
 */
class HorizonSnapshotCronJob extends CronJob {
    public function displayName(): string {
        return 'ep-horizon-snapshot';
    }

    public function handle(Kernel $artisan): void {
        $artisan->call('horizon:snapshot');
    }
}
