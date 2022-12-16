<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Listeners\DatabaseListener;
use App\Services\Logger\Listeners\DataLoaderListener;
use App\Services\Logger\Listeners\EloquentListener;
use App\Services\Logger\Listeners\LogListener;
use App\Services\Logger\Listeners\QueueListener;
use App\Utils\Providers\EventsProvider;
use App\Utils\Providers\ServiceServiceProvider;

use function config;

class Provider extends ServiceServiceProvider {
    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        LogListener::class,
        QueueListener::class,
        DatabaseListener::class,
        EloquentListener::class,
        DataLoaderListener::class,
    ];

    public function register(): void {
        parent::register();

        $this->app->singleton(Logger::class);
    }

    /**
     * @inheritDoc
     */
    protected function getListeners(): array {
        return config('ep.logger.enabled') ? parent::getListeners() : [];
    }
}
