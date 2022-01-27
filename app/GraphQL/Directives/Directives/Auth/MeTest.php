<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Auth;

use App\GraphQL\Directives\Directives\Mutation\Context\BuilderContext;
use App\GraphQL\Directives\Directives\Mutation\Context\Context;
use App\GraphQL\Resolvers\EmptyResolver;
use App\Models\Enums\UserType;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;
use App\Services\Auth\Permission;
use App\Services\Auth\Permissions;
use Closure;
use Illuminate\Database\Eloquent\Model;
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

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::manipulateTypeExtension
     * @covers ::manipulateTypeDefinition
     * @covers ::manipulateFieldDefinition
     * @covers ::addRequirements
     */
    public function testDirective(): void {
        Permissions::set([
            new class('a') extends Permission {
                // empty
            },
            new class('b') extends Permission {
                // empty
            },
            new class('c') extends Permission {
                // empty
            },
        ]);

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
        Closure $organizationFactory,
        Closure $userFactory,
    ): void {
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        Permissions::set([
            new class('a') extends Permission {
                // empty
            },
            new class('b') extends Permission {
                // empty
            },
            new class('c') extends Permission {
                // empty
            },
        ]);

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

        Permissions::set([
            new class('a') extends Permission {
                // empty
            },
        ]);

        $this->getGraphQLSchema(
        /** @lang GraphQL */
            <<<GRAPHQL
            type Query {
                value: String! @me(permissions: {$permissions}) @field(resolver: "{$resolver}")
            }
            GRAPHQL,
        );
    }

    /**
     * @covers ::getGateArguments
     *
     * @dataProvider dataProviderGetGateArguments
     *
     * @param array<mixed> $expected
     */
    public function testGetGateArguments(array $expected, Closure $root): void {
        $root = $root($this);
        $me   = new class() extends Me {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @return array<mixed>
             */
            public function getGateArguments(mixed $root): array {
                return parent::getGateArguments($root);
            }
        };

        $this->assertEquals($expected, $me->getGateArguments($root));
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
            'permissions empty'        => [
                new ErrorResponse(new InternalServerError()),
                [],
                static function () {
                    return null;
                },
                static function () {
                    return null;
                },
            ],
            'guest'                    => [
                new GraphQLUnauthenticated('value'),
                ['a', 'b', 'c'],
                static function () {
                    return null;
                },
                static function () {
                    return null;
                },
            ],
            'org user with permission' => [
                new GraphQLSuccess('value', null),
                ['a', 'b', 'c'],
                static function (): Organization {
                    return Organization::factory()->create();
                },
                static function (self $test, Organization $organization): User {
                    $user = User::factory()->create([
                        'organization_id' => $organization,
                        'permissions'     => ['a'],
                        'enabled'         => true,
                    ]);

                    OrganizationUser::factory()->create([
                        'organization_id' => $organization,
                        'user_id'         => $user,
                        'enabled'         => true,
                    ]);

                    return $user;
                },
            ],
            'user without permission'  => [
                new GraphQLUnauthorized('value'),
                ['a', 'b', 'c'],
                static function () {
                    return null;
                },
                static function () {
                    return User::factory()->make([
                        'permissions' => ['unknown'],
                    ]);
                },
            ],
            'root without permission'  => [
                new GraphQLSuccess('value', null),
                ['a', 'b', 'c'],
                static function () {
                    return null;
                },
                static function () {
                    return User::factory()->make([
                        'type'        => UserType::local(),
                        'permissions' => [],
                    ]);
                },
            ],
        ];
    }

    /**
     * @return array<string,array<mixed>,mixed>
     */
    public function dataProviderGetGateArguments(): array {
        $model = new class() extends Model {
            // empty
        };

        return [
            'mixed'                             => [
                [],
                static function (): ?Context {
                    return null;
                },
            ],
            Context::class                      => [
                [$model],
                static function () use ($model): Context {
                    return new class(null, $model) extends Context {
                        // empty
                    };
                },
            ],
            Context::class.' (null)'            => [
                [],
                static function (): Context {
                    return new class(null, null) extends Context {
                        // empty
                    };
                },
            ],
            BuilderContext::class.' (no model)' => [
                [$model::class],
                static function () use ($model): Context {
                    return new class(null, null, $model->newQuery()) extends BuilderContext {
                        // empty
                    };
                },
            ],
            BuilderContext::class               => [
                [$model],
                static function () use ($model): Context {
                    return new class(null, $model, $model->newQuery()) extends BuilderContext {
                        // empty
                    };
                },
            ],
        ];
    }
    // </editor-fold>
}
