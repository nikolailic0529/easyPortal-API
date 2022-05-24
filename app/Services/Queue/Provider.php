<?php declare(strict_types = 1);

namespace App\Services\Queue;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function boot(): void {
        $this->bootSnapshots();
    }

    protected function bootSnapshots(): void {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->callAfterResolving(
            Schedule::class,
            static function (Schedule $schedule, Container $container): void {
                $config = $container->make(Repository::class);
                $cron   = $config->get('ep.queue.snapshot.cron') ?: '*/5 * * * *';

                $schedule
                    ->command('horizon:snapshot')
                    ->cron($cron);
            },
        );
    }
}
