<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Queries\Me;
use App\Models\User;
use App\Services\Auth0\AuthService;
use Auth0\Login\Auth0Service;
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
 * @coversDefaultClass \App\GraphQL\Mutations\AuthSignInByCode
 */
class AuthSignInByCodeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<mixed>|null $userInfo
     */
    public function testInvoke(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $userInfo = null,
        Closure $foundUserFactory = null,
    ): void {
        // Prepare
        $this->setTenant($tenantFactory);
        $this->setUser($userFactory);

        // Mock
        $found        = $foundUserFactory ? $foundUserFactory($this) : null;
        $service      = Mockery::mock(AuthService::class);
        $signInByCode = $service->shouldReceive('signInByCode');
        $rememberUser = $service->shouldReceive('rememberUser');

        $service->shouldReceive('getService')->andReturn(
            Mockery::mock(Auth0Service::class),
        );

        $this->app->bind(AuthService::class, static function () use ($service): AuthService {
            return $service;
        });

        if ($expected instanceof GraphQLSuccess) {
            $signInByCode->once()->andReturn($userInfo);

            if ($found) {
                $rememberUser->once()->andReturnFalse();
            } else {
                $rememberUser->never();
            }
        } else {
            $signInByCode->never();
            $rememberUser->never();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation AuthSignInByCode($code: String!, $state: String!) {
                authSignInByCode(code: $code, state: $state) {
                    id,
                    family_name,
                    given_name
                }
            }', ['code' => '123', 'state' => '123'])
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
            new GuestDataProvider('authSignInByCode'),
            new ArrayDataProvider([
                'auth failed'                 => [
                    new GraphQLSuccess('authSignInByCode', null),
                    null,
                    null,
                ],
                'auth successful but no user' => [
                    new GraphQLSuccess('authSignInByCode', null),
                    ['profile' => ['sub' => '123']],
                    null,
                ],
                'auth successful'             => [
                    new GraphQLSuccess('authSignInByCode', Me::class),
                    ['profile' => ['sub' => '123']],
                    static function (): User {
                        return User::factory()->create(['sub' => '123']);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
