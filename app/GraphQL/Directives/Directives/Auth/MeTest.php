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
use Tests\WithOrganization;
use Tests\WithUser;

use function addslashes;
use function implode;
use function json_encode;
use function sprintf;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Auth\Me
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class MeTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    public function testDirective(): void {
        $this->app->make(Permissions::class)->set([
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
                    value: String! @authMe @field(resolver: "{$resolver}")
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
     * @dataProvider dataProviderResolveFieldPermissions
     *
     * @param array<string>       $permissions
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testResolveFieldPermissions(
        Response $expected,
        array $permissions,
        mixed $orgFactory,
        mixed $userFactory,
    ): void {
        $this->setUser($userFactory, $this->setOrganization($orgFactory));

        $this->app->make(Permissions::class)->set([
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
                    value: String! @authMe(permissions: {$permissions}) @field(resolver: "{$resolver}")
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
                    value(arg: Boolean = true @authMe): String! @field(resolver: "{$resolver}")
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

    public function testGetRequirements(): void {
        $resolver    = addslashes(EmptyResolver::class);
        $permissions = json_encode(['a', 'unknown']);

        self::expectExceptionObject(new InvalidArgumentException(sprintf(
            'Unknown permissions: `%s`',
            implode('`, `', ['unknown']),
        )));

        $this->app->make(Permissions::class)->set([
            new class('a') extends Permission {
                // empty
            },
        ]);

        $this->getGraphQLSchema(
        /** @lang GraphQL */
            <<<GRAPHQL
            type Query {
                value: String! @authMe(permissions: {$permissions}) @field(resolver: "{$resolver}")
            }
            GRAPHQL,
        );
    }

    /**
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
             * @inheritDoc
             */
            public function getGateArguments(mixed $root): array {
                return parent::getGateArguments($root);
            }
        };

        self::assertEquals($expected, $me->getGateArguments($root));
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
                new GraphQLSuccess('value'),
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
                new GraphQLSuccess('value'),
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
                new GraphQLSuccess('value'),
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
     * @return array<string,array<mixed>>
     */
    public function dataProviderGetGateArguments(): array {
        $model = new class() extends Model {
            public function getMorphClass(): string {
                return 'Model';
            }
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
                [$model->getMorphClass()],
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
