<?php declare(strict_types = 1);

namespace App\Services\KeyCloak\Jobs;

use App\Services\KeyCloak\Importer\Status;
use App\Services\KeyCloak\Importer\UsersImporter;
use App\Services\Queue\CronJob;
use App\Services\Queue\Progress;
use App\Services\Queue\Progressable;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Throwable;

/**
 * Sync application users with KeyCloak.
 */
class SyncUsersCronJob extends CronJob implements Progressable {
    public function displayName(): string {
        return 'ep-keycloak-sync-users';
    }

    public function __invoke(
        QueueableConfigurator $configurator,
        Service $service,
        UsersImporter $importer,
    ): void {
        $this->process($configurator, $service, $importer);
    }

    protected function process(
        QueueableConfigurator $configurator,
        Service $service,
        UsersImporter $importer,
        int $chunk = null,
        int $limit = null,
    ): void {
        $config   = $configurator->config($this);
        $state    = $this->getState($service) ?: $this->getDefaultState($config);
        $continue = $state->continue;

        $importer
            ->onInit(function (Status $status) use ($service, $state): void {
                $this->updateState($service, $state, $status);
            })
            ->onChange(function (Status $status) use ($service, $state): void {
                $this->updateState($service, $state, $status);
                $this->ping();
            })
            ->onFinish(function () use ($service): void {
                $this->resetState($service);
            })
            ->import($continue, $chunk, $limit);
    }

    public function getProgressCallback(): callable {
        return function (Service $service): ?Progress {
            $state    = $this->getState($service);
            $progress = null;

            if ($state) {
                $progress = new Progress($state->total, $state->processed);
            }

            return $progress;
        };
    }

    public function getResetProgressCallback(): callable {
        return function (Service $service): bool {
            $this->resetState($service);

            return true;
        };
    }

    protected function getState(Service $service): ?SyncUserState {
        $state = null;

        try {
            $state = $service->get($this, static function (array $state): SyncUserState {
                return new SyncUserState($state);
            });
        } catch (Throwable) {
            // empty
        }

        return $state;
    }

    protected function updateState(Service $service, SyncUserState $state, Status $status): SyncUserState {
        return $service->set($this, new SyncUserState([
            'continue'  => $status->continue,
            'total'     => $status->total,
            'processed' => $state->processed + $status->processed,
        ]));
    }

    protected function resetState(Service $service): void {
        $service->delete($this);
    }

    protected function getDefaultState(QueueableConfig $config): SyncUserState {
        return new SyncUserState();
    }
}
