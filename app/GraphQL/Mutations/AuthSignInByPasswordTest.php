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
 * @coversDefaultClass \App\GraphQL\Mutations\AuthSignInByPassword
 */
class AuthSignInByPasswordTest extends TestCase {
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

        $data = [
            'username' => $this->faker->email,
            'password' => $this->faker->password,
        ];

        // Mock
        $found        = $foundUserFactory ? $foundUserFactory($this) : null;
        $service      = Mockery::mock(AuthService::class);
        $signInByCode = $service->shouldReceive('signInByPassword');
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
            ->graphQL(/** @lang GraphQL */ '
                mutation AuthSignInByPassword(
                    $username: String!
                    $password: String!
                ) {
                    authSignInByPassword(
                        username: $username
                        password: $password
                    ) {
                        id
                        family_name
                        given_name
                    }
                }
            ', $data)
            ->assertThat($expected);
    }

    public function testInvokeValidation(): void {
        $this->markTestIncomplete('Not implemented.');
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
            new GuestDataProvider('authSignInByPassword'),
            new ArrayDataProvider([
                'auth failed'                 => [
                    new GraphQLSuccess('authSignInByPassword', null),
                    null,
                    null,
                ],
                'auth successful but no user' => [
                    new GraphQLSuccess('authSignInByPassword', null),
                    ['profile' => ['sub' => '123']],
                    null,
                ],
                'auth successful'             => [
                    new GraphQLSuccess('authSignInByPassword', Me::class),
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
