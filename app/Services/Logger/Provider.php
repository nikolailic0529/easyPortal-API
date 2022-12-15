<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Contracts\Registrable;
use App\Services\Logger\Listeners\DatabaseListener;
use App\Services\Logger\Listeners\DataLoaderListener;
use App\Services\Logger\Listeners\EloquentListener;
use App\Services\Logger\Listeners\LogListener;
use App\Services\Logger\Listeners\QueueListener;
use App\Utils\Providers\EventServiceProvider;

use function config;
use function is_a;

class Provider extends EventServiceProvider {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
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

        $this->registerLogger();
        $this->registerListeners();
    }

    protected function registerLogger(): void {
        $this->app->singleton(Logger::class);
    }

    protected function registerListeners(): void {
        foreach ($this->getListeners() as $listener) {
            $this->app->singleton($listener);

            if (is_a($listener, Registrable::class, true)) {
                $listener::register();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function getListeners(): array {
        return config('ep.logger.enabled') ? parent::getListeners() : [];
    }
}
