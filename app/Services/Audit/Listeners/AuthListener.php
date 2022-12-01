<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\Contexts\Auth\SignIn;
use App\Services\Audit\Contexts\Auth\SignInFailed;
use App\Services\Audit\Contexts\Auth\SignOut;
use App\Services\Audit\Enums\Action;
use App\Services\Keycloak\Auth\UserProvider;
use App\Services\Organization\OrganizationProvider;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Events\Dispatcher;
use LogicException;

class AuthListener extends Listener {
    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(Login::class, $this::class);
        $dispatcher->listen(Logout::class, $this::class);
        $dispatcher->listen(Failed::class, $this::class);
        $dispatcher->listen(PasswordReset::class, $this::class);
    }

    public function __invoke(object $event): void {
        $org     = null;
        $user    = null;
        $action  = null;
        $context = null;

        if ($event instanceof Login) {
            $org     = $this->getUserOrganization($event->user);
            $user    = $event->user;
            $action  = Action::authSignedIn();
            $context = new SignIn($event->guard, $event->remember);
        } elseif ($event instanceof Logout) {
            // Guest can call sign out too (it may be useful to recreate session).
            // Anyway, in this case there are no reasons to record the event.
            if ($event->user !== null) {
                $org     = $this->getUserOrganization($event->user);
                $user    = $event->user;
                $action  = Action::authSignedOut();
                $context = new SignOut($event->guard);
            }
        } elseif ($event instanceof Failed) {
            // There are no guarantees that the User will be signed out and/or
            // that the Current Organization will be reset after signing out.
            // So we cannot use User properties or Current Organization.
            $org     = $event->credentials[UserProvider::CREDENTIAL_ORGANIZATION] ?? null;
            $user    = $event->user;
            $email   = $event->credentials[UserProvider::CREDENTIAL_EMAIL] ?? null;
            $action  = Action::authFailed();
            $context = new SignInFailed($event->guard, $email);
        } elseif ($event instanceof PasswordReset) {
            $org     = $this->org;
            $user    = $event->user;
            $action  = Action::authPasswordReset();
            $context = null;
        } else {
            throw new LogicException('Unknown event O_O');
        }

        if ($action) {
            $this->auditor->create($org, $action, null, $context, $user);
        }
    }

    protected function getUserOrganization(Authenticatable $user): OrganizationProvider|Organization|string|null {
        // For the `local` user the current organization is always unknown.
        $org = null;

        if ($user instanceof User) {
            if ($user->type !== UserType::local()) {
                $org = $user->organization_id ?? $this->org;
            } else {
                $org = null;
            }
        } else {
            $org = $this->org;
        }

        return $org;
    }
}
