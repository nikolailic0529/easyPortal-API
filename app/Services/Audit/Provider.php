<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Services\Audit\Listeners\AuditableListener;
use App\Services\Audit\Listeners\AuthListener;
use App\Services\Audit\Listeners\ExportListener;
use App\Services\Audit\Listeners\InvitationListener;
use App\Services\Audit\Listeners\OrganizationListener;
use App\Utils\Providers\EventServiceProvider;
use App\Utils\Providers\EventsProvider;

class Provider extends EventServiceProvider {
    /**
     * @var array<class-string<EventsProvider>>
     */
    protected array $listeners = [
        AuditableListener::class,
        AuthListener::class,
        ExportListener::class,
        InvitationListener::class,
        OrganizationListener::class,
    ];
}
