<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth\Organization;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Organization;
use App\Services\Keycloak\Keycloak;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\GuestDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;
use Throwable;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Auth\Organization\SignIn
 */
class SignInTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $orgFactory,
        Closure $userFactory = null,
        Closure $organizationFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        // Organization
        $id = $this->faker->uuid();

        if ($organizationFactory) {
            $passed = $organizationFactory($this, $org, $user);

            if ($passed) {
                $id = $passed->getKey();
            }
        }

        // Mock

        if ($expected instanceof GraphQLSuccess) {
            $this->override(Keycloak::class, static function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('getAuthorizationUrl')
                    ->once()
                    ->andReturn('http://example.com/');
            });
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($id: ID!) {
                    auth {
                        organization(id: $id) {
                            signIn {
                                result
                                url
                            }
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $id,
                ],
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
            new UnknownOrganizationDataProvider(),
            new GuestDataProvider('auth'),
            new ArrayDataProvider([
                'organization not exists' => [
                    new GraphQLError('auth', static function (): Throwable {
                        return new ObjectNotFound(
                            (new Organization())->getMorphClass(),
                        );
                    }),
                    static function (): mixed {
                        return null;
                    },
                ],
                'redirect to login'       => [
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragmentSchema('organization.signIn', self::class),
                        new JsonFragment('organization.signIn', [
                            'result' => true,
                            'url'    => 'http://example.com/',
                        ]),
                    ),
                    static function (): Organization {
                        return Organization::factory()->create();
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
