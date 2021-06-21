<?php declare(strict_types = 1);

namespace App\Services\DataLoader\Jobs;

use App\Jobs\NamedJob;
use App\Services\DataLoader\Importers\AssetsImporter;
use App\Services\DataLoader\Importers\Status;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Queueables\CronJob;
use Throwable;

/**
 * Imports assets.
 */
class AssetsImporterCronJob extends CronJob implements ShouldBeUnique, NamedJob {
    public function displayName(): string {
        return 'ep-data-loader-assets-importer';
    }

    public function handle(
        AssetsImporter $importer,
        Repository $cache,
    ): void {
        $state    = $this->getState($cache);
        $from     = Date::make($state->from);
        $update   = false;
        $continue = $state->continue;

        $importer
            ->onChange(function (array $items, Status $status) use ($cache, $state): void {
                $this->setState($cache, $status, $state);
            })
            ->onFinish(function () use ($cache): void {
                $this->resetState($cache);
            })
            ->import($update, $from, $continue);
    }

    protected function getState(Repository $cache): ImporterState {
        $state = new ImporterState();

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
