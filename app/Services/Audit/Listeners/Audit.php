<?php declare(strict_types = 1);

namespace App\Services\Audit\Listeners;

use App\Events\Subscriber;
use App\Models\Model;
use App\Services\Audit\Auditor;
use App\Services\Audit\Enums\Action;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Events\RouteMatched;

use function in_array;

class Audit implements Subscriber {
    public function __construct(
        protected Auditor $auditor,
    ) {
        // empty
    }

    public function signIn(Login $event): void {
        $user = $event->user;
        if ($user instanceof Model) {
            $this->auditor->create(Action::authSignedIn(), $user);
        }
    }

    public function signOut(Logout $event): void {
        $user = $event->user;
        if ($user instanceof Model) {
            $this->auditor->create(Action::authSignedOut(), $user);
        }
    }

    public function routeMatched(RouteMatched $event): void {
        $routes = ['download.csv', 'download.excel', 'download.pdf'];
        if (in_array($event->route->getName(), $routes, true)) {
            $this->auditor->create(Action::exported());
        }
    }

    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(Login::class, [$this::class, 'signIn']);
        $dispatcher->listen(Logout::class, [$this::class, 'signOut']);
        $dispatcher->listen(RouteMatched::class, [$this::class, 'routeMatched']);
    }
}
