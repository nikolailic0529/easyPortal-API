<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs\Cron;

use App\Services\KeyCloak\Commands\UsersSync;
use App\Services\Queue\CronJob;
use Illuminate\Contracts\Console\Kernel;

/**
 * Sync application permissions with KeyCloak.
 */
class PermissionsSynchronizer extends CronJob {
    public function displayName(): string {
        return 'ep-keycloak-permissions-synchronizer';
    }

    public function __invoke(Kernel $artisan): void {
        $artisan->call(UsersSync::class);
    }
}
