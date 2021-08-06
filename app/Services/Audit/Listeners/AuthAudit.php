<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Events\Subscriber;
use App\Services\Audit\Auditor;
use App\Services\Audit\Enums\Action;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Events\Dispatcher;

class AuthAudit implements Subscriber {
    public function __construct(
        protected Auditor $auditor,
    ) {
        // empty
    }

    public function signIn(Login $event): void {
        $this->auditor->create($event->user, Action::signedIn());
    }

    public function signOut(Logout $event): void {
        $this->auditor->create($event->user, Action::signedOut());
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(Login::class, [$this::class, 'signIn']);
        $dispatcher->listen(Logout::class, [$this::class, 'signOut']);
    }
}
