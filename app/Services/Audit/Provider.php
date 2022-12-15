<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Services\Audit\Listeners\AuditableListener;
use App\Services\Audit\Listeners\AuthListener;
use App\Services\Audit\Listeners\ExportListener;
use App\Services\Audit\Listeners\InvitationListener;
use App\Services\Audit\Listeners\OrganizationListener;
use App\Utils\Providers\EventServiceProvider;

class Provider extends EventServiceProvider {
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint
     */
    protected array $listeners = [
        AuditableListener::class,
        AuthListener::class,
        ExportListener::class,
        InvitationListener::class,
        OrganizationListener::class,
    ];
}
