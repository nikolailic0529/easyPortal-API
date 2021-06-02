<?php declare(strict_types = 1);

namespace App\Services\Logger;

use App\Services\Logger\Listeners\QueueListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class Provider extends EventServiceProvider {
    /**
     * The subscriber classes to register.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<class-string<\App\Events\Subscriber>>
     */
    protected $subscribe = [
        QueueListener::class,
    ];

    public function register(): void {
        // Logs not needed while tests
        if ($this->app->runningUnitTests()) {
            return;
        }

        // Register events
        parent::register();

        // and other classes
        $this->registerLogger();
    }

    protected function registerLogger(): void {
        $this->app->singleton(Logger::class);
    }
}
