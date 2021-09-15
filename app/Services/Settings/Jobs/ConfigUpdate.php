<?php declare(strict_types = 1);

namespace App\Services\Settings\Jobs;

use App\Services\Queue\Job;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;

/**
 * Updates application config (if cached) and restart queue to apply setting changes.
 */
class ConfigUpdate extends Job {
    public function displayName(): string {
        return 'ep-settings-config-update';
    }

    public function __invoke(Application $app, Kernel $artisan): void {
        try {
            if ($app instanceof CachesConfiguration && $app->configurationIsCached()) {
                $artisan->call('config:cache');
            }
        } finally {
            $artisan->call('queue:restart');
        }
    }
}
