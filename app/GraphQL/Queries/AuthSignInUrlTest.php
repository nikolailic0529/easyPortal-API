<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use Auth0\Login\Auth0Service;
use Closure;
use Illuminate\Http\RedirectResponse;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Mockery;
use Tests\DataProviders\GraphQL\GuestDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\AuthSignInUrl
 */
class AuthSignInUrlTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(Response $expected, Closure $tenantFactory, Closure $userFactory = null): void {
        // Prepare
        $this->setTenant($tenantFactory);
        $this->setUser($userFactory);

        // Mock
        $auth0  = Mockery::mock(Auth0Service::class);
        $method = 'login';

        if ($expected instanceof OkResponse) {
            $auth0->shouldReceive($method)->once()->andReturn(
                new RedirectResponse('http://example.com/'),
            );
        } else {
            $auth0->shouldReceive($method)->never();
        }

        $this->app->bind(Auth0Service::class, static function () use ($auth0): Auth0Service {
            return $auth0;
        });

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
                authSignInUrl
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
            new GuestDataProvider(),
            new ArrayDataProvider([
                'redirect to login' => [
                    new GraphQLSuccess('authSignInUrl', AuthSignInUrl::class),
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
