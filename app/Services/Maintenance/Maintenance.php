<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Maintenance\Jobs\CompleteCronJob;
use App\Services\Maintenance\Jobs\StartCronJob;
use App\Services\Settings\Settings as SettingsService;
use Cron\CronExpression;
use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\CronableConfig;
use LastDragon_ru\LaraASP\Queue\Contracts\Cronable;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

class Maintenance {
    public function __construct(
        protected Application $app,
        protected Repository $config,
        protected SettingsService $settings,
        protected QueueableConfigurator $configurator,
        protected Storage $storage,
    ) {
        // empty
    }

    public function getSettings(): ?Settings {
        $data     = $this->storage->load();
        $settings = $data ? Settings::make($data) : null;

        if ($this->app->isDownForMaintenance()) {
            $settings        ??= new Settings();
            $settings->enabled = true;
        }

        return $settings;
    }

    public function isEnabled(): bool {
        return (bool) $this->getSettings()?->enabled;
    }

    public function start(DateTimeInterface $end, string $message = null, bool $force = false): bool {
        if ($this->isEnabled()) {
            return true;
        }

        $result = $this->schedule(Date::now(), $end, $message);

        if ($force) {
            $result = $result && $this->enable();
        } else {
            $result = $result && $this->app->make(StartCronJob::class)->dispatch();
        }

        return $result;
    }

    public function stop(bool $force = false): bool {
        $result = false;

        if ($force) {
            $result = $this->disable();
        } else {
            $result = $this->isJobScheduled(CompleteCronJob::class)
                || (bool) $this->app->make(CompleteCronJob::class)->dispatch();
        }

        return $result;
    }

    public function schedule(DateTimeInterface $start, DateTimeInterface $end, string $message = null): bool {
        // Update jobs
        $notify = $this->config->get('ep.maintenance.notify.before');
        $notify = $notify ? Date::make($start)->sub($notify) : $start;

        $this->settings->setEditableSettings([
            'EP_MAINTENANCE_START_CRON'    => $this->cron($start),
            'EP_MAINTENANCE_NOTIFY_CRON'   => $this->cron($notify),
            'EP_MAINTENANCE_COMPLETE_CRON' => $this->cron($end),
        ]);

        // Save
        $settings          = $this->getSettings() ?? new Settings();
        $settings->message = $message;
        $settings->start   = $start;
        $settings->end     = $end;

        return $this->save($settings);
    }

    /**
     * @internal
     */
    public function enable(): bool {
        $settings          = $this->getSettings() ?? new Settings();
        $settings->enabled = true;

        return $this->save($settings);
    }

    /**
     * @internal
     */
    public function disable(): bool {
        return $this->reset();
    }

    /**
     * @internal
     */
    public function markAsNotified(): bool {
        // Settings?
        $settings = $this->getSettings();

        if (!$settings) {
            return false;
        }

        // Update
        $settings->notified = true;

        return $this->save($settings);
    }

    protected function save(Settings $settings): bool {
        return $this->storage->save($settings->toArray());
    }

    protected function reset(): bool {
        return $this->storage->delete(true);
    }

    protected function cron(DateTimeInterface $datetime): string {
        return $datetime->format('i G j n w');
    }

    /**
     * @param class-string<Cronable> $job
     */
    protected function isJobScheduled(string $job): bool {
        // Enabled?
        $job     = $this->app->make($job);
        $config  = $this->configurator->config($job);
        $enabled = $config->get(CronableConfig::Enabled);

        if (!$enabled) {
            return false;
        }

        // Scheduled within a day?
        $now       = Date::now();
        $max       = Date::now()->addDay();
        $cron      = $config->get(CronableConfig::Cron);
        $timezone  = $config->get(CronableConfig::Timezone);
        $scheduled = null;

        try {
            if ($cron) {
                $scheduled = (new CronExpression($cron))->getNextRunDate(timeZone: $timezone);
            }
        } catch (Exception $exception) {
            // empty
        }

        return $scheduled && $now <= $scheduled && $scheduled <= $max;
    }
}
