<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Jobs\Cron;

use App\Services\Keycloak\Commands\PermissionsSync;
use App\Services\Queue\CronJob;
use Illuminate\Contracts\Console\Kernel;

/**
 * Sync application permissions with Keycloak.
 */
class PermissionsSynchronizer extends CronJob {
    public function displayName(): string {
        return 'ep-keycloak-permissions-synchronizer';
    }

    public function __invoke(Kernel $artisan): void {
        $artisan->call(PermissionsSync::class);
    }
}
