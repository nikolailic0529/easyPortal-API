<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Services\Auth0\AuthService;
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
        // Prepare
        $this->setTenant($tenantFactory);
        $this->setUser($userFactory);

        // Mock
        $service = Mockery::mock(AuthService::class);
        $method  = 'getSignInLink';

        $service->shouldReceive('getService')->andReturn(
            Mockery::mock(Auth0Service::class),
        );

        if ($expected instanceof OkResponse) {
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
