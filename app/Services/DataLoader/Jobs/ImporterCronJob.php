<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\NamedJob;
use App\Services\DataLoader\Importers\Importer;
use App\Services\DataLoader\Importers\Status;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\QueueableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
use Throwable;

/**
 * Base Importer job.
 */
abstract class ImporterCronJob extends CronJob implements ShouldBeUnique, NamedJob {
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
        $state    = $this->getState($cache, $config);
        $from     = Date::make($state->from);
        $chunk    = $config->setting('chunk');
        $update   = $config->setting('update');
        $continue = $state->continue;

        $importer
            ->onChange(function (array $items, Status $status) use ($cache, $state): void {
                $this->setState($cache, $status, $state);
            })
            ->onFinish(function () use ($cache): void {
                $this->resetState($cache);
            })
            ->import($update, $from, $continue, $chunk);
    }

    protected function getDefaultState(QueueableConfig $config): ImporterState {
        $from   = null;
        $expire = $config->setting('expire');

        if ($expire) {
            $from = Date::now()->sub($expire)->format(DateTimeInterface::ATOM);
        }

        return new ImporterState([
            'from' => $from,
        ]);
    }

    protected function getState(Repository $cache, QueueableConfig $config): ImporterState {
        $state = $this->getDefaultState($config);

        try {
            if ($cache->has($this->displayName())) {
                $state = new ImporterState($cache->get($this->displayName()));
            }
        } catch (Throwable) {
            // empty
        }

        return $state;
    }

    protected function setState(Repository $cache, Status $status, ImporterState $initial): void {
        $cache->set($this->displayName(), (new ImporterState([
            'from'      => $status->from?->format(DateTimeInterface::ATOM),
            'continue'  => $status->continue,
            'processed' => $initial->processed + $status->processed,
        ]))->jsonSerialize());
    }

    protected function resetState(Repository $cache): void {
        $cache->forget($this->displayName());
    }
}
