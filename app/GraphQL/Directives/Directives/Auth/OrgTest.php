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

use function addslashes;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Auth\Org
 *
 * @phpstan-import-type OrganizationFactory from \Tests\WithOrganization
 * @phpstan-import-type UserFactory from \Tests\WithUser
 */
class OrgTest extends TestCase {
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
        self::assertGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected('~expected.graphql', '~schema.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

    /**
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveField
     *
     * @param OrganizationFactory $organizationFactory
     * @param UserFactory         $userFactory
     */
    public function testResolveField(Response $expected, mixed $organizationFactory, mixed $userFactory): void {
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        $resolver = addslashes(EmptyResolver::class);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value: String! @authOrg @field(resolver: "{$resolver}")
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
                static function (TestCase $test, ?Organization $organization): User {
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
    // </editor-fold>
}