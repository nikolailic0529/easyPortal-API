<?php declare(strict_types = 1);

namespace App\Services\Search\Jobs;

use App\Services\Queue\Contracts\Progressable;
use App\Services\Queue\CronJob;
use App\Services\Queue\Progress;
use App\Services\Search\Processor\Processor;
use App\Services\Search\Processor\Status;
use App\Services\Search\Service;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use Throwable;

/**
 * Rebuilds Search Index for Model.
 */
abstract class UpdateIndexCronJob extends CronJob implements Progressable {
    /**
     * @param class-string<\Illuminate\Database\Eloquent\Model&\App\Services\Search\Eloquent\Searchable> $model
     */
    protected function process(
        QueueableConfigurator $configurator,
        Service $service,
        Processor $updater,
        string $model,
    ): void {
        $config   = $configurator->config($this);
        $state    = $this->getState($service) ?: $this->getDefaultState($config);
        $from     = Date::make($state->from);
        $continue = $state->continue;

        $updater
            ->onInit(function (Status $status) use ($service, $state): void {
                $this->updateState($service, $state, $status);
            })
            ->onChange(function (Collection $items, Status $status) use ($service, $state): void {
                $this->updateState($service, $state, $status);
                $this->ping();
            })
            ->onFinish(function () use ($service): void {
                $this->resetState($service);
            })
            ->update($model, $from, $continue);
    }

    protected function getDefaultState(QueueableConfig $config): UpdateIndexState {
        return new UpdateIndexState();
    }

    protected function getState(Service $service): ?UpdateIndexState {
        $state = null;

        try {
            $state = $service->get($this, static function (array $state): UpdateIndexState {
                return new UpdateIndexState($state);
            });
        } catch (Throwable) {
            // empty
        }

        return $state;
    }

    protected function updateState(Service $service, UpdateIndexState $state, Status $status): UpdateIndexState {
        return $service->set($this, new UpdateIndexState([
            'from'      => $status->from?->format(DateTimeInterface::ATOM),
            'continue'  => $status->continue,
            'total'     => $status->total,
            'processed' => $state->processed + $status->processed,
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
