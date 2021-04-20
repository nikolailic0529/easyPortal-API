<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\GraphQL\Queries\Me;
use App\Http\Middleware\SetLocale;
use App\Models\Organization;
use App\Models\User;
use Closure;
use Illuminate\Auth\AuthManager;
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
        $this->markTestIncomplete('FIXME [KeyCloak] Not implemented.');

        // Prepare
        $tenant = $this->setTenant($tenantFactory);
        $user   = $this->setUser($userFactory, $tenant);

        // Mock
        // TODO [Auth0] Instead `AuthManager::login()` and `AuthManager::logout()`
        //      probably will be better mock Auth0Service to check that data
        //      really deleted.
        $found        = $foundUserFactory ? $foundUserFactory($this, $tenant, $user) : null;
        $service      = Mockery::mock(AuthService::class);
        $signInByCode = $service->shouldReceive('signInByCode');
        $rememberUser = $service->shouldReceive('rememberUser');
        $authManager  = Mockery::mock(AuthManager::class);
        $user         = $authManager->shouldReceive('user');
        $login        = $authManager->shouldReceive('login');
        $logout       = $authManager->shouldReceive('logout');

        $service->shouldReceive('getService')->andReturn(
            Mockery::mock(Auth0Service::class),
        );

        $this->app->bind(AuthService::class, static function () use ($service): AuthService {
            return $service;
        });

        $this->app->bind(AuthManager::class, static function () use ($authManager): AuthManager {
            return $authManager;
        });

        if ($expected instanceof GraphQLSuccess) {
            $signInByCode->once()->andReturn($userInfo);

            if ($found) {
                if ($userInfo['profile']['email_verified']) {
                    $user->once()->andReturn($found);
                    $login->once()->andReturns();
                    $logout->never();
                    $rememberUser->once()->andReturnFalse();
                } else {
                    $user->once()->andReturnNull();
                    $login->never();
                    $logout->once()->andReturns();
                    $rememberUser->never();
                }
            } else {
                $user->once()->andReturnNull();
                $login->never();
                $logout->once()->andReturns();
                $rememberUser->never();
            }
        } else {
            $signInByCode->never();
            $rememberUser->never();
            $logout->never();
        }

        // Disable middleware and it affected the test instead of user get called once,
        // it get called twice due to SetLocale middleware
        $this->withoutMiddleware(SetLocale::class);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ 'mutation AuthSignInByCode($code: String!, $state: String!) {
                authSignInByCode(code: $code, state: $state) {
                    id
                    family_name
                    given_name
                    locale
                    root
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
                'auth failed'                          => [
                    new GraphQLSuccess('authSignInByCode', null),
                    null,
                    null,
                ],
                'auth successful but no user'          => [
                    new GraphQLSuccess('authSignInByCode', null),
                    ['profile' => ['sub' => '123']],
                    null,
                ],
                'auth successful but email unverified' => [
                    new GraphQLSuccess('authSignInByCode', null),
                    [
                        'profile' => [
                            'sub'            => '123',
                            'email_verified' => false,
                        ],
                    ],
                    static function (self $test, ?Organization $organization): User {
                        return User::factory()->create([
                            'organization_id' => $organization,
                            'sub'             => '123',
                        ]);
                    },
                ],
                'auth successful'                      => [
                    new GraphQLSuccess('authSignInByCode', Me::class),
                    [
                        'profile' => [
                            'sub'            => '123',
                            'email_verified' => true,
                            'given_name'     => '123',
                            'family_name'    => '123',
                            'picture'        => '123',
                        ],
                    ],
                    static function (self $test, ?Organization $organization): User {
                        return User::factory()->create([
                            'organization_id' => $organization,
                            'sub'             => '123',
                        ]);
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
