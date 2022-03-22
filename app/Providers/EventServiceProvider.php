<?php declare(strict_types = 1);

namespace App\Providers;

use App\Events\Subscriber;
use App\Services\Audit\Listeners\Audit;
use App\Services\Organization\Listeners\OrganizationUpdater;
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
        Audit::class,
    ];

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
