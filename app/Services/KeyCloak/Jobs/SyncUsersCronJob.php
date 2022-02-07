<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use App\Services\KeyCloak\Importer\UsersImporter;
use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\CronJob;
use App\Services\Queue\Progressable;
use App\Utils\Processor\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Sync application users with KeyCloak.
 */
class SyncUsersCronJob extends CronJob implements Progressable {
    use ProcessorJob;

    public function displayName(): string {
        return 'ep-keycloak-sync-users';
    }

    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(UsersImporter::class);
    }
}
