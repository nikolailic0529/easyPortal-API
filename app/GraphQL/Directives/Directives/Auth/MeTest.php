<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\GraphQL\Resolvers\EmptyResolver;
use App\Models\Enums\UserType;
use App\Models\User;
use App\Services\Auth\Auth;
use App\Services\Auth\Permission;
use Closure;
use InvalidArgumentException;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\StatusCodes\InternalServerError;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\ErrorResponse;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLUnauthenticated;
use Tests\GraphQL\GraphQLUnauthorized;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

use function addslashes;
use function implode;
use function json_encode;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Auth\Me
 */
class MeTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Prepare">
    // =========================================================================
    public function setUp(): void {
        parent::setUp();

        $this->override(Auth::class, static function (): Auth {
            return new class() extends Auth {
                /**
                 * @inheritDoc
                 */
                public function getPermissions(): array {
                    return [
                        new Permission('a'),
                        new Permission('b'),
                        new Permission('c'),
                    ];
                }
            };
        });
    }
    // </editor-fold>

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
                    value: String! @me @field(resolver: "{$resolver}")
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
     * @dataProvider dataProviderResolveFieldPermissions
     *
     * @param array<string> $permissions
     */
    public function testResolveFieldPermissions(
        Response $expected,
        array $permissions,
        Closure $userFactory,
    ): void {
        $this->setUser($userFactory);

        $resolver    = addslashes(EmptyResolver::class);
        $permissions = json_encode($permissions);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    value: String! @me(permissions: {$permissions}) @field(resolver: "{$resolver}")
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
     * @covers ::getRequirements
     */
    public function testGetRequirements(): void {
        $resolver    = addslashes(EmptyResolver::class);
        $permissions = json_encode(['a', 'unknown']);

        $this->expectExceptionObject(new InvalidArgumentException(sprintf(
            'Unknown permissions: `%s`',
            implode('`, `', ['unknown']),
        )));

        $this->getGraphQLSchema(
        /** @lang GraphQL */
            <<<GRAPHQL
            type Query {
                value: String! @me(permissions: {$permissions}) @field(resolver: "{$resolver}")
            }
            GRAPHQL,
        );
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
                new GraphQLUnauthenticated('value'),
                static function () {
                    return null;
                },
            ],
            'user'  => [
                new GraphQLSuccess('value', null),
                static function () {
                    return User::factory()->make();
                },
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderResolveFieldPermissions(): array {
        return [
            'permissions empty'       => [
                new ErrorResponse(new InternalServerError()),
                [],
                static function () {
                    return null;
                },
            ],
            'guest'                   => [
                new GraphQLUnauthenticated('value'),
                ['a', 'b', 'c'],
                static function () {
                    return null;
                },
            ],
            'user with permission'    => [
                new GraphQLSuccess('value', null),
                ['a', 'b', 'c'],
                static function () {
                    return User::factory()->make([
                        'permissions' => ['a'],
                    ]);
                },
            ],
            'user without permission' => [
                new GraphQLUnauthorized('value'),
                ['a', 'b', 'c'],
                static function () {
                    return User::factory()->make([
                        'permissions' => ['unknown'],
                    ]);
                },
            ],
            'root without permission' => [
                new GraphQLSuccess('value', null),
                ['a', 'b', 'c'],
                static function () {
                    return User::factory()->make([
                        'type'        => UserType::local(),
                        'permissions' => [],
                    ]);
                },
            ],
        ];
    }
    // </editor-fold>
}
