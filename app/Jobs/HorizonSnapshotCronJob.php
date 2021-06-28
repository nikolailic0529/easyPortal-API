<?php declare(strict_types = 1);

namespace App\Jobs;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;

/**
 * Creates Horizon snapshot.
 */
class HorizonSnapshotCronJob extends CronJob implements ShouldBeUnique, NamedJob {
    public function displayName(): string {
        return 'ep-horizon-snapshot';
    }

    public function handle(Kernel $artisan): void {
        $artisan->call('horizon:snapshot');
    }
}
