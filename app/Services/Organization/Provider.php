<?php declare(strict_types = 1);

namespace App\Services\Organization;

use App\Services\Organization\Listeners\OrganizationUpdater;
use App\Utils\Providers\EventsProvider;
use App\Utils\Providers\ServiceServiceProvider;

class Provider extends ServiceServiceProvider {
    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        OrganizationUpdater::class,
    ];

    public function register(): void {
        parent::register();

        $this->app->singleton(RootOrganization::class);
        $this->app->singleton(CurrentOrganization::class);
    }
}
