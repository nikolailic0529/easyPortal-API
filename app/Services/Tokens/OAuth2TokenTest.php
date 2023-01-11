<?php declare(strict_types = 1);

namespace App\Services\Tokens;

use Exception;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Mockery;
use Tests\TestCase;

/**
 * @internal
 * @covers \App\Services\Tokens\OAuth2Token
 */
class OAuth2TokenTest extends TestCase {
    public function testGetAccessToken(): void {
        $params = [1, 2, 3];
        $token  = Mockery::mock(AccessTokenInterface::class);
        $token
            ->shouldReceive('getToken')
            ->atLeast()
            ->once()
            ->andReturn('new');
        $token
            ->shouldReceive('hasExpired')
            ->once()
            ->andReturn(false);
        $token
            ->shouldReceive('jsonSerialize')
            ->once()
            ->andReturn([]);

        $cache = Mockery::mock(Repository::class);
        $cache
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);
        $cache
            ->shouldReceive('set')
            ->once()
            ->andReturn(true);

        $factory = Mockery::mock(Factory::class);
        $factory
            ->shouldReceive('store')
            ->once()
            ->andReturn($cache);

        $provider = Mockery::mock(AbstractProvider::class);
        $provider
            ->shouldReceive('getAccessToken')
            ->with('client_credentials', $params)
            ->once()
            ->andReturn($token);

        $service = Mockery::mock(OAuth2Token::class);
        $service->shouldAllowMockingProtectedMethods();
        $service->makePartial();
        $service
            ->shouldReceive('getTokenParameters')
            ->once()
            ->andReturn($params);
        $service
            ->shouldReceive('getProvider')
            ->once()
            ->andReturn($provider);
        $service
            ->shouldReceive('getService')
            ->atLeast()
            ->once()
            ->andReturn($this->app->make(Service::class, [
                'factory' => $factory,
            ]));

        self::assertEquals($token->getToken(), $service->getAccessToken());
        self::assertEquals($token->getToken(), $service->getAccessToken());
    }

    public function testReset(): void {
        $cache = Mockery::mock(Repository::class);
        $cache
            ->shouldReceive('delete')
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('get')
            ->once()
            ->andReturn(null);

        $factory = Mockery::mock(Factory::class);
        $factory
            ->shouldReceive('store')
            ->once()
            ->andReturn($cache);

        $service = Mockery::mock(OAuth2Token::class);
        $service->shouldAllowMockingProtectedMethods();
        $service->makePartial();
        $service
            ->shouldReceive('getService')
            ->atLeast()
            ->once()
            ->andReturn($this->app->make(Service::class, [
                'factory' => $factory,
            ]));
        $service
            ->shouldReceive('getToken')
            ->once()
            ->andThrow(new Exception('no token'));

        $service->reset();

        self::expectExceptionMessage('no token');

        $service->getAccessToken();
    }
    // </editor-fold>
}
