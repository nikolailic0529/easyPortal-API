<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth;

use App\Services\Keycloak\Keycloak;
use App\Services\Keycloak\OAuth2\Provider;
use App\Services\Organization\CurrentOrganization;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\UnknownUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Auth\SignOut
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class SignOutTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory       $orgFactory
     * @param UserFactory               $userFactory
     * @param Closure(static):void|null $prepare
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        // Mock
        if ($prepare) {
            $prepare($this);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation {
                    auth {
                        signOut {
                            result
                            url
                        }
                    }
                }
                GRAPHQL,
            )
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
            new UnknownOrgDataProvider(),
            new UnknownUserDataProvider(),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragment('signOut', [
                            'result' => true,
                            'url'    => null,
                        ]),
                    ),
                    static function (TestCase $test): void {
                        $provider = Mockery::mock(Provider::class);
                        $keycloak = Mockery::mock(Keycloak::class, [
                            $test->app->make(Repository::class),
                            $test->app->make(Session::class),
                            $test->app->make(AuthManager::class),
                            $test->app->make(CurrentOrganization::class),
                            $test->app->make(UrlGenerator::class),
                            $test->app->make(ExceptionHandler::class),
                        ]);
                        $keycloak->makePartial();
                        $keycloak
                            ->shouldReceive('getProvider')
                            ->once()
                            ->andReturn($provider);

                        $test->override(Keycloak::class, static function () use ($keycloak): Keycloak {
                            return $keycloak;
                        });
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
