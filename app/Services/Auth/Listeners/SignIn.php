<?php declare(strict_types = 1);

namespace App\Services\Auth\Listeners;

use App\Events\Subscriber;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;

class SignIn implements Subscriber {
    public function subscribe(Dispatcher $dispatcher): void {
        $dispatcher->listen(Login::class, $this::class);
    }

    public function __invoke(Login $event): void {
        if ($event->user instanceof User) {
            $event->user->previous_sign_in = Date::now();
            $event->user->save();
        }
    }
}
