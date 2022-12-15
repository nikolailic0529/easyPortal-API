<?php declare(strict_types = 1);

namespace App\Providers;

use App\Utils\Eloquent\Events\Subject;
use App\Utils\Providers\EventServiceProvider as ServiceProvider;
use App\Utils\Providers\EventsProvider;

class EventServiceProvider extends ServiceProvider {
    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        Subject::class,
    ];

    public function register(): void {
        parent::register();

        $this->app->singleton(Subject::class);
    }

    /**
     * Register any events for your application.
     */
    public function boot(): void {
        // empty
    }
}
