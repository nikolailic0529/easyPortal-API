<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives;

use App\Models\Organization;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithTenant;

use function addslashes;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Organization
 */
class OrganizationTest extends TestCase {
    use WithGraphQLSchema;
    use WithTenant;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::manipulateTypeExtension
     * @covers ::manipulateTypeDefinition
     * @covers ::manipulateFieldDefinition
     * @covers ::addRequirements
     */
    public function testDirective(): void {
        $expected = $this->getTestData()->content('~expected.graphql');
        $actual   = $this->getGraphQLSchema($this->getTestData()->content('~schema.graphql'));

        $this->assertEquals($expected, $actual);
    }

    /**
     * @covers ::resolveField
     *
     * @dataProvider dataProviderResolveField
     */
    public function testResolveField(Response $expected, Closure $tenantFactory, Closure $userFactory): void {
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        $resolver = addslashes(OrganizationDirectiveTest_Resolver::class);

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
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderResolveField(): array {
        return [
            'no tenant - no user'               => [
                new GraphQLUnauthenticated('value'),
                static function () {
                    return null;
                },
                static function () {
                    return null;
                },
            ],
            'tenant - no user'                  => [
                new GraphQLUnauthenticated('value'),
                static function () {
                    return Organization::factory()->make();
                },
                static function () {
                    return null;
                },
            ],
            'tenant - user'                     => [
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
            'tenant - user from another tenant' => [
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

// @phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses
// @phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps

/**
 * @internal
 * @noinspection PhpMultipleClassesDeclarationsInOneFile
 */
class OrganizationDirectiveTest_Resolver {
    public function __invoke(): string {
        return __FUNCTION__;
    }
}
