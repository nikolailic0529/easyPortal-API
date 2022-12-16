<?php declare(strict_types = 1);

namespace App\Services\Auth\Listeners;

use App\Models\User;
use App\Utils\Providers\EventsProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Date;

class SignIn implements EventsProvider {
    /**
     * @inheritDoc
     */
    public static function getEvents(): array {
        return [
            Login::class,
        ];
    }

    public function __invoke(Login $event): void {
        if ($event->user instanceof User) {
            $event->user->previous_sign_in = Date::now();
            $event->user->save();
        }
    }
}
