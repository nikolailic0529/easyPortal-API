<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Auth\Organization;

use App\GraphQL\Directives\Directives\Mutation\Exceptions\ObjectNotFound;
use App\Models\Organization;
use App\Models\User;
use App\Services\Keycloak\Exceptions\Auth\StateMismatch;
use App\Services\Keycloak\Keycloak;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Mockery\MockInterface;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\AuthGuestDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;
use Throwable;

/**
 * @internal
 * @covers \App\GraphQL\Mutations\Auth\Organization\Authorize
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class AuthorizeTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $organizationFactory = null,
        ?string $state = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        // Mock
        $me      = User::factory()->make([
            'id' => '7ad49dda-6b3c-43f7-81bb-1d86260a6e07',
        ]);
        $id      = $this->faker->uuid();
        $code    = $this->faker->word();
        $state ??= $this->faker->word();

        if ($organizationFactory) {
            $id = $organizationFactory($this, $org, $user)?->getKey() ?? $id;
        }

        if ($expected instanceof GraphQLSuccess) {
            $this->override(
                Keycloak::class,
                static function (MockInterface $mock) use ($me, $code, $state): void {
                    $mock
                        ->shouldReceive('authorize')
                        ->with(Mockery::any(), $code, $state)
                        ->once()
                        ->andReturnUsing(static function () use ($me, $state): User {
                            if ($state === 'mismatch') {
                                throw new StateMismatch();
                            }

                            return $me;
                        });
                },
            );
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation authorize($id: ID!, $input: AuthOrganizationAuthorizeInput!) {
                    auth {
                        organization(id: $id) {
                            authorize(input: $input) {
                                result
                                me {
                                    id
                                }
                            }
                        }
                    }
                }
                GRAPHQL,
                [
                    'id'    => $id,
                    'input' => [
                        'code'  => $code,
                        'state' => $state,
                    ],
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
            new UnknownOrgDataProvider(),
            new AuthGuestDataProvider('auth'),
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
                    null,
                ],
                'state mismatch'          => [
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragment('organization.authorize', [
                            'result' => false,
                            'me'     => null,
                        ]),
                    ),
                    static function (): Organization {
                        return Organization::factory()->create();
                    },
                    'mismatch',
                ],
                'success'                 => [
                    new GraphQLSuccess(
                        'auth',
                        new JsonFragment('organization.authorize', [
                            'result' => true,
                            'me'     => [
                                'id' => '7ad49dda-6b3c-43f7-81bb-1d86260a6e07',
                            ],
                        ]),
                    ),
                    static function (): Organization {
                        return Organization::factory()->create();
                    },
                    null,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
