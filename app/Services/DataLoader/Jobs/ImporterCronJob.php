<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Services\DataLoader\Importers\Importer;
use App\Services\DataLoader\Importers\Status;
use App\Services\Queue\CronJob;
use App\Services\Queue\Progress;
use App\Services\Queue\Progressable;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
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
        Repository $cache,
        Importer $importer,
        QueueableConfigurator $configurator,
    ): void {
        $config   = $configurator->config($this);
        $state    = $this->getState($cache) ?: $this->getDefaultState($config);
        $from     = Date::make($state->from);
        $chunk    = $config->setting('chunk');
        $update   = $state->update;
        $continue = $state->continue;

        $importer
            ->onInit(function (Status $status) use ($cache, $state): void {
                $this->updateState($cache, $state, $status);
            })
            ->onChange(function (array $items, Status $status) use ($cache, $state): void {
                $this->updateState($cache, $state, $status);
                $this->stop();
            })
            ->onFinish(function () use ($cache): void {
                $this->resetState($cache);
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

    protected function getState(Repository $cache): ?ImporterState {
        $state = null;

        try {
            if ($cache->has($this->displayName())) {
                $state = new ImporterState($cache->get($this->displayName()));
            }
        } catch (Throwable) {
            // empty
        }

        return $state;
    }

    protected function updateState(Repository $cache, ImporterState $initial, Status $status): void {
        $this->saveState($cache, new ImporterState([
            'from'      => $status->from?->format(DateTimeInterface::ATOM),
            'continue'  => $status->continue,
            'total'     => $status->total,
            'processed' => $initial->processed + $status->processed,
        ]));
    }

    protected function saveState(Repository $cache, ImporterState $state): void {
        $cache->set($this->displayName(), $state->jsonSerialize());
    }

    protected function resetState(Repository $cache): void {
        $cache->forget($this->displayName());
    }

    public function getProgressCallback(): callable {
        return function (Repository $cache): ?Progress {
            $state    = $this->getState($cache);
            $progress = null;

            if ($state) {
                $progress = new Progress($state->total, $state->processed);
            }

            return $progress;
        };
    }

    public function getResetProgressCallback(): callable {
        return function (Repository $cache): bool {
            $this->resetState($cache);

            return true;
        };
    }
}
