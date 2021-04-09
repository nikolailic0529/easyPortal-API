<?php declare(strict_types = 1);

namespace App\Services\Settings\Jobs;

use App\Jobs\NamedJob;
use App\Services\Settings\Settings;
use Illuminate\Contracts\Console\Kernel;
use LastDragon_ru\LaraASP\Queue\Queueables\Job;

/**
 * Updates application config (if cached) and restart queue to apply setting changes.
 */
class ConfigUpdate extends Job implements NamedJob {
    public function displayName(): string {
        return 'ep-settings-config-update';
    }

    public function handle(Kernel $artisan, Settings $settings): void {
        try {
            if ($settings->isCached()) {
                $artisan->call('config:cache');
            }
        } finally {
            $artisan->call('queue:restart');
        }
    }
}
