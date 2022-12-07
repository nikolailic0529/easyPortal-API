<?php declare(strict_types = 1);

namespace App\Services\Keycloak\Jobs\Cron;

use App\Services\Keycloak\Importer\UsersImporter;
use App\Services\Queue\Concerns\ProcessorJob;
use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Utils\Processor\Contracts\Processor;
use Illuminate\Contracts\Container\Container;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;

/**
 * Sync application users with Keycloak.
 *
 * @template TProcessor of UsersImporter
 */
class UsersSynchronizer extends CronJob implements Progressable {
    /**
     * @use ProcessorJob<TProcessor>
     */
    use ProcessorJob;

    public function displayName(): string {
        return 'ep-keycloak-users-synchronizer';
    }

    /**
     * @return TProcessor
     */
    protected function makeProcessor(Container $container, QueueableConfig $config): Processor {
        return $container->make(UsersImporter::class);
    }
}
