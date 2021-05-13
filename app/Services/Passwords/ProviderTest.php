<?php declare(strict_types = 1);

namespace App\Services\Passwords;

use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Passwords\Provider
 */
class ProviderTest extends TestCase {
    /**
     * @covers ::register
     * @covers ::registerPasswordBroker
     */
    public function testRegisterPasswordBroker(): void {
        $this->assertInstanceOf(PasswordBrokerManager::class, $this->app->make('auth.password'));
    }
}
