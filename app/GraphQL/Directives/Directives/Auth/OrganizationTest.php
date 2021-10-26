<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\GraphQL\Resolvers\EmptyResolver;
use App\Models\Organization;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

use function addslashes;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Auth\Organization
 */
class OrganizationTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::manipulateTypeExtension
     * @covers ::manipulateTypeDefinition
     * @covers ::manipulateFieldDefinition
     * @covers ::addRequirements
     */
    public function testDirective(): void {
        $this->assertGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected('~expected.graphql', '~schema.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

    /**
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveField
     */
    public function testResolveField(Response $expected, Closure $organizationFactory, Closure $userFactory): void {
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        $resolver = addslashes(EmptyResolver::class);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value: String! @organization @field(resolver: "{$resolver}")
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
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveFieldRootOnly
     */
    public function testResolveFieldRootOnly(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory,
        bool $isRootOrganization = false,
    ): void {
        $organization = $this->setOrganization($organizationFactory);

        $this->setUser($userFactory, $organization);

        if ($isRootOrganization) {
            $this->setRootOrganization($organization);
        }

        $resolver = addslashes(EmptyResolver::class);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value: String! @organization(root: true) @field(resolver: "{$resolver}")
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
     * @return array<mixed>
     */
    public function dataProviderResolveField(): array {
        return [
            'no organization - no user'                     => [
                new GraphQLUnauthenticated('value'),
                static function () {
                    return null;
                },
                static function () {
                    return null;
                },
            ],
            'organization - no user'                        => [
                new GraphQLUnauthenticated('value'),
                static function () {
                    return Organization::factory()->make();
                },
                static function () {
                    return null;
                },
            ],
            'organization - user'                           => [
                new GraphQLSuccess('value', null),
                static function () {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, Organization $organization) {
                    return User::factory()->create([
                        'organization_id' => $organization,
                    ]);
                },
            ],
            'organization - user from another organization' => [
                new GraphQLUnauthorized('value'),
                static function () {
                    return Organization::factory()->create();
                },
                static function () {
                    return User::factory()->create();
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderResolveFieldRootOnly(): array {
        return [
            'no organization - no user'                          => [
                new GraphQLUnauthenticated('value'),
                static function () {
                    return null;
                },
                static function () {
                    return null;
                },
                false,
            ],
            'organization - no user'                             => [
                new GraphQLUnauthenticated('value'),
                static function () {
                    return Organization::factory()->make();
                },
                static function () {
                    return null;
                },
                false,
            ],
            'root organization - no user'                        => [
                new GraphQLUnauthenticated('value'),
                static function () {
                    return Organization::factory()->make();
                },
                static function () {
                    return null;
                },
                true,
            ],
            'organization - user'                                => [
                new GraphQLUnauthorized('value'),
                static function () {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, Organization $organization) {
                    return User::factory()->create([
                        'organization_id' => $organization,
                    ]);
                },
                false,
            ],
            'root organization - user'                           => [
                new GraphQLSuccess('value', null),
                static function () {
                    return Organization::factory()->create();
                },
                static function (TestCase $test, Organization $organization) {
                    return User::factory()->create([
                        'organization_id' => $organization,
                    ]);
                },
                true,
            ],
            'organization - user from another organization'      => [
                new GraphQLUnauthorized('value'),
                static function () {
                    return Organization::factory()->create();
                },
                static function () {
                    return User::factory()->create();
                },
                false,
            ],
            'root organization - user from another organization' => [
                new GraphQLUnauthorized('value'),
                static function () {
                    return Organization::factory()->create();
                },
                static function () {
                    return User::factory()->create();
                },
                true,
            ],
        ];
    }
    // </editor-fold>
}
