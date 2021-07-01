<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use App\Services\Queue\CronJob;
use Illuminate\Contracts\Console\Kernel;

/**
 * Sync application permissions with KeyCloak.
 */
class SyncPermissionsCronJob extends CronJob {
    public function displayName(): string {
        return 'ep-keycloak-sync-permissions';
    }

    public function handle(Kernel $artisan): void {
        $artisan->call('ep:keycloak-sync-permissions');
    }
}
