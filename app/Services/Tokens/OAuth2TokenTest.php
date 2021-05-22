<?php declare(strict_types = 1);

namespace App\Services\Tokens;

use Exception;
use Illuminate\Contracts\Cache\Repository;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\Services\Tokens\OAuth2Token
 */
class OAuth2TokenTest extends TestCase {
    /**
     * @covers ::getAccessToken
     */
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
            ->shouldReceive('has')
            ->once()
            ->andReturn(false);
        $cache
            ->shouldReceive('set')
            ->once()
            ->andReturn(true);

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
            ->shouldReceive('getCache')
            ->atLeast()
            ->once()
            ->andReturn($cache);

        $this->assertEquals($token->getToken(), $service->getAccessToken());
        $this->assertEquals($token->getToken(), $service->getAccessToken());
    }

    /**
     * @covers ::reset
     */
    public function testReset(): void {
        $cache = Mockery::mock(Repository::class);
        $cache
            ->shouldReceive('forget')
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('has')
            ->once()
            ->andThrow(new Exception('no token'));

        $service = Mockery::mock(OAuth2Token::class);
        $service->shouldAllowMockingProtectedMethods();
        $service->makePartial();
        $service
            ->shouldReceive('getCache')
            ->atLeast()
            ->once()
            ->andReturn($cache);

        $this->expectDeprecationMessage('no token');

        $service->reset();
        $service->getAccessToken();
    }
    // </editor-fold>
}
