<?php declare(strict_types = 1);

namespace App\Services\Audit;

use App\Services\Audit\Listeners\AuditableListener;
use App\Services\Audit\Listeners\AuthListener;
use App\Services\Audit\Listeners\ExportListener;
use App\Services\Audit\Listeners\InvitationListener;
use App\Services\Audit\Listeners\OrganizationListener;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class Provider extends ServiceProvider {
    public function register(): void {
        parent::register();

        $this->booting(static function (Dispatcher $dispatcher): void {
            $dispatcher->subscribe(AuditableListener::class);
            $dispatcher->subscribe(AuthListener::class);
            $dispatcher->subscribe(ExportListener::class);
            $dispatcher->subscribe(InvitationListener::class);
            $dispatcher->subscribe(OrganizationListener::class);
        });
    }
}
