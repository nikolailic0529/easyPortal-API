<?php declare(strict_types = 1);

namespace App\Providers;

use App\Services\Organization\Listeners\OrganizationUpdater;
use App\Utils\Eloquent\Events\Subject;
use App\Utils\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected array $listeners = [
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
}
