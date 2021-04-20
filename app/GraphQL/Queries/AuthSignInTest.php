<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\GuestDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\AuthSignIn
 */
class AuthSignInTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(Response $expected, Closure $tenantFactory, Closure $userFactory = null): void {
        $this->markTestIncomplete('FIXME [KeyCloak] Not implemented.');

        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        // Mock
        $service = Mockery::mock(AuthService::class);
        $method  = 'getSignInLink';

        $service->shouldReceive('getService')->andReturn(
            Mockery::mock(Auth0Service::class),
        );

        if ($expected instanceof GraphQLSuccess) {
            $service->shouldReceive($method)->once()->andReturn(
                'http://example.com/',
            );
        } else {
            $service->shouldReceive($method)->never();
        }

        $this->app->bind(AuthService::class, static function () use ($service): AuthService {
            return $service;
        });

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                authSignIn
            }')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new GuestDataProvider('authSignIn'),
            new ArrayDataProvider([
                'redirect to login' => [
                    new GraphQLSuccess('authSignIn', AuthSignIn::class),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
