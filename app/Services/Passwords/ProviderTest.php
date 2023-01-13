<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Passwords\Provider
 */
class ProviderTest extends TestCase {
    public function testRegisterPasswordBroker(): void {
        self::assertInstanceOf(PasswordBrokerManager::class, $this->app->make('auth.password'));
    }
}
