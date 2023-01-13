<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\GraphQL\Resolvers\EmptyResolver;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithUser;

use function addslashes;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Auth\Guest
 *
 * @phpstan-import-type UserFactory from WithUser
 */
class GuestTest extends TestCase {
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
     * @param UserFactory $userFactory
     */
    public function testResolveField(Response $expected, mixed $userFactory): void {
        $this->setUser($userFactory);

        $resolver = addslashes(EmptyResolver::class);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value: String! @authGuest @field(resolver: "{$resolver}")
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
     * @param UserFactory $userFactory
     */
    public function testResolveArg(Response $expected, mixed $userFactory): void {
        $this->setUser($userFactory);

        $resolver = addslashes(EmptyResolver::class);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value(arg: Boolean = true @authGuest): String! @field(resolver: "{$resolver}")
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
                new GraphQLSuccess('value'),
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
