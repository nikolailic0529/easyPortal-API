<?php declare(strict_types = 1);

namespace App\Services\Auth\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Auth\Listeners\SignIn
 */
class SignInTest extends TestCase {
    public function testInvoke(): void {
        $now        = Date::now()->setMicroseconds(0);
        $user       = User::factory()->make();
        $event      = new Login('test', $user, false);
        $dispatcher = $this->app->make(Dispatcher::class);

        Date::setTestNow($now);

        $dispatcher->dispatch($event);

        self::assertTrue($user->exists);
        self::assertEquals($now, $user->previous_sign_in);
    }
}
