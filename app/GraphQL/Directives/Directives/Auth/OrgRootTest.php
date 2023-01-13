<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\GraphQL\Resolvers\EmptyResolver;
use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithOrganization;
use Tests\WithUser;

use function addslashes;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Auth\OrgRoot
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class OrgRootTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    public function testDirective(): void {
        self::assertGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected('~expected.graphql', '~schema.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

    /**
     * @dataProvider dataProviderResolveField
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testResolveField(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory,
        bool $isRootOrganization = false,
    ): void {
        $org = $this->setOrganization($orgFactory);

        $this->setUser($userFactory, $org);

        if ($isRootOrganization) {
            $this->setRootOrganization($org);
        }

        $resolver = addslashes(EmptyResolver::class);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value: String! @authOrgRoot @field(resolver: "{$resolver}")
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    value
                }
                GRAPHQL,
            )
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderResolveField
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testResolveArg(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory,
        bool $isRootOrganization = false,
    ): void {
        $org = $this->setOrganization($orgFactory);

        $this->setUser($userFactory, $org);

        if ($isRootOrganization) {
            $this->setRootOrganization($org);
        }

        $resolver = addslashes(EmptyResolver::class);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value(arg: Boolean = true @authOrgRoot): String! @field(resolver: "{$resolver}")
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    value
                }
                GRAPHQL,
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{Response, OrganizationFactory, UserFactory}>
     */
    public function dataProviderResolveField(): array {
        return [
            'no organization - no user'                          => [
                new GraphQLUnauthenticated('value'),
                static function (): ?Organization {
                    return null;
                },
                static function (): ?User {
                    return null;
                },
                false,
            ],
            'organization - no user'                             => [
                new GraphQLUnauthenticated('value'),
                static function (): Organization {
                    return Organization::factory()->make();
                },
                static function (): ?User {
                    return null;
                },
                false,
            ],
            'root organization - no user'                        => [
                new GraphQLUnauthenticated('value'),
                static function (): Organization {
                    return Organization::factory()->make();
                },
                static function (): ?User {
                    return null;
                },
                true,
            ],
            'organization - user'                                => [
                new GraphQLUnauthorized('value'),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (TestCase $test): User {
                    return User::factory()->create([
                        //'organization_id' => $organization,
                    ]);
                },
                false,
            ],
            'root organization - user'                           => [
                new GraphQLSuccess('value'),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, ?Organization $organization): User {
                    return User::factory()->create([
                        'organization_id' => $organization,
                    ]);
                },
                true,
            ],
            'organization - user from another organization'      => [
                new GraphQLUnauthorized('value'),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (): User {
                    return User::factory()->create();
                },
                false,
            ],
            'root organization - user from another organization' => [
                new GraphQLUnauthorized('value'),
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (): User {
                    return User::factory()->create();
                },
                true,
            ],
        ];
    }
    // </editor-fold>
}
