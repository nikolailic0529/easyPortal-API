<?php declare(strict_types = 1);

namespace App\Providers;

use App\Events\Subscriber;
use App\Services\Organization\Listeners\OrganizationUpdater;
use App\Utils\Eloquent\Events\Subject;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    /**
     * The event listener mappings for the application.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // empty
    ];

    /**
     * The subscriber classes to register.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<class-string<Subscriber>>
     */
    protected $subscribe = [
        OrganizationUpdater::class,
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

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool {
        return false;
    }
}
