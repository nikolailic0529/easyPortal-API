<?php declare(strict_types = 1);

namespace App\Services\Settings\Jobs;

use App\Services\Queue\Job;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Contracts\Foundation\CachesRoutes;

/**
 * Updates application config and routes (if cached) and restart queue to apply
 * new settings.
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

            if ($app instanceof CachesRoutes && $app->routesAreCached()) {
                $artisan->call('route:cache');
            }
        } finally {
            $artisan->call('queue:restart');
        }
    }
}
