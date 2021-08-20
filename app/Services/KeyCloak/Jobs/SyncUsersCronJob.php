<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use App\Services\Queue\CronJob;
use Illuminate\Contracts\Console\Kernel;

/**
 * Sync application users with KeyCloak.
 */
class SyncUsersCronJob extends CronJob {
    public function displayName(): string {
        return 'ep-keycloak-sync-users';
    }

    public function __invoke(Kernel $artisan): void {
        $artisan->call('ep:keycloak-sync-users');
    }
}
