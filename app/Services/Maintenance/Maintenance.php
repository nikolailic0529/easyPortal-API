<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Maintenance\Jobs\DisableCronJob;
use App\Services\Maintenance\Jobs\EnableCronJob;
use App\Services\Settings\Settings as SettingsService;
use Cron\CronExpression;
use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Queue\Configs\CronableConfig;
use LastDragon_ru\LaraASP\Queue\QueueableConfigurator;

use function cache;
use function ltrim;

class Maintenance {
    public function __construct(
        protected Container $container,
        protected SettingsService $settings,
        protected QueueableConfigurator $configurator,
        protected Storage $storage,
    ) {
        // empty
    }

    public function getSettings(): ?Settings {
        $data     = $this->storage->load();
        $settings = $data ? Settings::make($data) : null;

        return $settings;
    }

    public function isEnabled(): bool {
        return (bool) $this->getSettings()?->enabled;
    }

    public function start(DateTimeInterface $end, string $message = null): bool {
        if ($this->isEnabled()) {
            return true;
        }

        return $this->schedule(Date::now(), $end, $message)
            && $this->container->make(EnableCronJob::class)->dispatch();
    }

    public function stop(bool $force = false): bool {
        return ($force || !$this->isJobScheduled(DisableCronJob::class)) && $this->disable();
    }

    public function schedule(DateTimeInterface $start, DateTimeInterface $end, string $message = null): bool {
        // Update jobs
        $this->settings->setEditableSettings([
            'EP_MAINTENANCE_ENABLE_CRON'  => $this->cron($start),
            'EP_MAINTENANCE_DISABLE_CRON' => $this->cron($end),
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

    protected function save(Settings $settings): bool {
        return $this->storage->save($settings->toArray());
    }

    protected function reset(): bool {
        return $this->storage->delete(true);
    }

    protected function cron(DateTimeInterface $datetime): string {
        return ltrim($datetime->format('i G j n w'), '0');
    }

    /**
     * @param class-string<\LastDragon_ru\LaraASP\Queue\Contracts\Cronable> $job
     */
    protected function isJobScheduled(string $job): bool {
        // Enabled?
        $job     = $this->container->make($job);
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
