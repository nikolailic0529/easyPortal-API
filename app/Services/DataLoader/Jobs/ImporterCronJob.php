<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importers\Importer;
use App\Services\DataLoader\Importers\Status;
use App\Services\DataLoader\Service;
use App\Services\Queue\CronJob;
use App\Services\Queue\Progress;
use App\Services\Queue\Progressable;
use DateTimeInterface;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Throwable;

/**
 * Base Importer job.
 */
abstract class ImporterCronJob extends CronJob implements Progressable {
    /**
     * @return array<mixed>
     */
    public function getQueueConfig(): array {
        return [
                'settings' => [
                    'chunk'  => null,
                    'expire' => null,
                    'update' => false,
                ],
            ] + parent::getQueueConfig();
    }

    protected function process(
        Service $service,
        Importer $importer,
        QueueableConfigurator $configurator,
    ): void {
        $config   = $configurator->config($this);
        $state    = $this->getState($service) ?: $this->getDefaultState($config);
        $from     = Date::make($state->from);
        $chunk    = $config->setting('chunk');
        $update   = $state->update;
        $continue = $state->continue;

        $importer
            ->onInit(function (Status $status) use ($service, $state): void {
                $this->updateState($service, $state, $status);
            })
            ->onChange(function (array $models, Status $status) use ($service, $state): void {
                $this->updateState($service, $state, $status);
                $this->ping();
            })
            ->onFinish(function () use ($service): void {
                $this->resetState($service);
            })
            ->import($update, $from, $continue, $chunk);
    }

    protected function getDefaultState(QueueableConfig $config): ImporterState {
        $from   = null;
        $update = $config->setting('update');
        $expire = $config->setting('expire');

        if ($expire) {
            $from = Date::now()->sub($expire)->format(DateTimeInterface::ATOM);
        }

        return new ImporterState([
            'from'   => $from,
            'update' => $update,
        ]);
    }

    protected function getState(Service $service): ?ImporterState {
        $state = null;

        try {
            $state = $service->get($this, static function (array $state): ImporterState {
                return new ImporterState($state);
            });
        } catch (Throwable) {
            // empty
        }

        return $state;
    }

    protected function updateState(Service $service, ImporterState $initial, Status $status): void {
        $service->set($this, new ImporterState([
            'from'      => $status->from?->format(DateTimeInterface::ATOM),
            'continue'  => $status->continue,
            'total'     => $status->total,
            'processed' => $initial->processed + $status->processed,
        ]));
    }

    protected function resetState(Service $service): void {
        $service->delete($this);
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
}
