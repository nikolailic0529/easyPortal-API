<?php declare(strict_types = 1);

namespace App\Services\Maintenance;

use App\Services\Settings\Settings as SettingsService;
use DateTimeInterface;

use function ltrim;

class Maintenance {
    public function __construct(
        protected SettingsService $settings,
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

    public function enable(): bool {
        $settings          = $this->getSettings() ?? new Settings();
        $settings->enabled = true;

        return $this->save($settings);
    }

    public function disable(): bool {
        // Disable jobs
        $this->settings->setEditableSettings([
            'EP_MAINTENANCE_ENABLE_ENABLED'  => false,
            'EP_MAINTENANCE_DISABLE_ENABLED' => false,
        ]);

        // Save
        return $this->reset();
    }

    public function schedule(DateTimeInterface $start, DateTimeInterface $end, string $message = null): bool {
        // Update jobs
        $this->settings->setEditableSettings([
            'EP_MAINTENANCE_ENABLE_ENABLED'  => true,
            'EP_MAINTENANCE_ENABLE_CRON'     => $this->cron($start),
            'EP_MAINTENANCE_DISABLE_ENABLED' => true,
            'EP_MAINTENANCE_DISABLE_CRON'    => $this->cron($end),
        ]);

        // Save
        $settings          = $this->getSettings() ?? new Settings();
        $settings->message = $message;
        $settings->start   = $start;
        $settings->end     = $end;

        return $this->save($settings);
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
}
