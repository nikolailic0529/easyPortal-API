<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\Organization\Listeners\OrganizationUpdater;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    /**
     * The event listener mappings for the application.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<array<string>>
     */
    protected $listen = [
        // empty
    ];

    /**
     * The subscriber classes to register.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     *
     * @var array<class-string<\App\Events\Subscriber>>
     */
    protected $subscribe = [
        OrganizationUpdater::class,
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void {
        // empty
    }
}
