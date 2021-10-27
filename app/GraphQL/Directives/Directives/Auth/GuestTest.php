<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\GraphQL\Resolvers\EmptyResolver;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

use function addslashes;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Auth\Guest
 */
class GuestTest extends TestCase {
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
    public function testResolveField(Response $expected, Closure $userFactory): void {
        $this->setUser($userFactory);

        $resolver = addslashes(EmptyResolver::class);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value: String! @guest @field(resolver: "{$resolver}")
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
            'guest' => [
                new GraphQLSuccess('value', null),
                static function () {
                    return null;
                },
            ],
            'user'  => [
                new GraphQLUnauthenticated('value'),
                static function () {
                    return User::factory()->make();
                },
            ],
        ];
    }
    // </editor-fold>
}