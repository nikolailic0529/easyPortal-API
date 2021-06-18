<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use App\Jobs\NamedJob;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;

/**
 * Update customer.
 */
class SyncPermissions extends CronJob implements ShouldBeUnique, NamedJob {
    public function displayName(): string {
        return 'ep:keycloak-sync-permissions';
    }

    public function handle(Kernel $artisan): void {
        $artisan->call('ep:keycloak-sync-permissions');
    }
}
