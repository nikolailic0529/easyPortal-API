<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Listeners\DatabaseListener;
use App\Services\Logger\Listeners\DataLoaderListener;
use App\Services\Logger\Listeners\EloquentListener;
use App\Services\Logger\Listeners\LogListener;
use App\Services\Logger\Listeners\QueueListener;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->registerLogger();
        $this->registerListeners();
    }

    protected function registerLogger(): void {
        $this->app->singleton(Logger::class);
    }

    protected function registerListeners(): void {
        $this->booting(static function (Repository $config, Dispatcher $dispatcher): void {
            // Enabled?
            if (!$config->get('ep.logger.enabled')) {
                return;
            }

            // Subscribe
            $dispatcher->subscribe(LogListener::class);
            $dispatcher->subscribe(QueueListener::class);
            $dispatcher->subscribe(DatabaseListener::class);
            $dispatcher->subscribe(EloquentListener::class);
            $dispatcher->subscribe(DataLoaderListener::class);
        });
    }
}
