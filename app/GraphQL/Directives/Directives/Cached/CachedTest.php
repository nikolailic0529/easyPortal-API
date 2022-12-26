<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use App\GraphQL\Cache;
use App\Models\Organization;
use App\Utils\Cache\CacheKey;
use Closure;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use Mockery;
use Nuwave\Lighthouse\Execution\ResolveInfo;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithOrganization;

use function addslashes;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Cached\Cached
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class CachedTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::handleField
     *
     * @dataProvider dataProviderHandleField
     *
     * @param OrganizationFactory $orgFactory
     */
    public function testResolveField(
        Response $expected,
        mixed $orgFactory,
        string $schema,
        string $graphql,
    ): void {
        $this->setOrganization($orgFactory);
        $this->setSettings([
            'ep.cache.graphql.enabled'   => true,
            'ep.cache.graphql.threshold' => null,
        ]);

        $resolver = Mockery::spy(static function (): string {
            return 'value';
        });

        $this->mockResolver($resolver);

        $this->useGraphQLSchema($schema);

        $this
            ->graphQL($graphql)
            ->assertThat($expected);
        $this
            ->graphQL($graphql)
            ->assertThat($expected);

        $resolver->shouldHaveBeenCalled()->once();
    }

    /**
     * @covers ::handleField
     */
    public function testResolveFieldDisabled(): void {
        $this->setOrganization(static function (): Organization {
            return Organization::factory()->create();
        });
        $this->setSettings([
            'ep.cache.graphql.enabled' => false,
        ]);

        $expected = new GraphQLSuccess('root', json_encode('value'));
        $resolver = Mockery::spy(static function (): string {
            return 'value';
        });
        $graphql  = /** @lang GraphQL */
            <<<'GRAPHQL'
            query {
                root
            }
            GRAPHQL;

        $this->mockResolver($resolver);

        $this->useGraphQLSchema(
        /** @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
                root: String @cached @mock
            }
            GRAPHQL,
        );

        $this
            ->graphQL($graphql)
            ->assertThat($expected);
        $this
            ->graphQL($graphql)
            ->assertThat($expected);

        $resolver->shouldHaveBeenCalled()->twice();
    }

    /**
     * @covers ::getCacheKey
     *
     * @dataProvider dataProviderGetCacheKey
     */
    public function testGetCacheKey(
        Response $expected,
        Closure $factory,
        string $schema,
        string $graphql,
    ): void {
        $factory($this);

        $directive = new class() extends Cached implements FieldResolver {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty;
            }

            public function name(): string {
                return 'cached';
            }

            public function handleField(FieldValue $fieldValue, Closure $next): FieldValue {
                return $next($fieldValue);
            }

            public function resolveField(FieldValue $fieldValue): FieldValue {
                return $fieldValue->setResolver(
                    function (
                        mixed $root,
                        array $args,
                        GraphQLContext $context,
                        ResolveInfo $resolveInfo,
                    ): mixed {
                        return $this->getCacheKey($root, $args, $context, $resolveInfo);
                    },
                );
            }
        };

        $this->app->make(DirectiveLocator::class)
            ->setResolved('cached', $directive::class);

        $this
            ->useGraphQLSchema($schema)
            ->graphQL($graphql)
            ->assertThat($expected);
    }

    /**
     * @covers ::getResolveMode
     *
     * @dataProvider dataProviderGetResolveMode
     *
     * @param OrganizationFactory $orgFactory
     */
    public function testGetResolveMode(
        Response $expected,
        mixed $orgFactory,
        string $schema,
        string $graphql,
    ): void {
        $this->setOrganization($orgFactory);

        $directive = new class() extends Cached implements FieldResolver {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty;
            }

            public function handleField(FieldValue $fieldValue, Closure $next): FieldValue {
                return $next($fieldValue);
            }

            public function resolveField(FieldValue $fieldValue): FieldValue {
                return $fieldValue->setResolver(function (): string {
                    return (string) $this->getResolveMode();
                });
            }
        };

        $this->app->make(DirectiveLocator::class)
            ->setResolved('cached', $directive::class);

        $this
            ->useGraphQLSchema($schema)
            ->graphQL($graphql)
            ->assertThat($expected);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveCachedActual(): void {
        $key      = new CacheKey([$this->faker->word()]);
        $value    = $this->faker->sentence();
        $root     = null;
        $args     = [];
        $context  = Mockery::mock(GraphQLContext::class);
        $resolve  = Mockery::mock(ResolveInfo::class);
        $resolver = Mockery::spy(static function (): void {
            // empty
        });
        $cache    = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(false);

        $directive = Mockery::mock(Cached::class, [$cache]);
        $directive->shouldAllowMockingProtectedMethods();
        $directive->makePartial();
        $directive
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(
                new CachedValue(Date::now(), Date::now(), null, $value),
            );

        self::assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveCachedExpiredLockable(): void {
        $key      = new CacheKey([$this->faker->word()]);
        $value    = $this->faker->sentence();
        $root     = null;
        $args     = [];
        $context  = Mockery::mock(GraphQLContext::class);
        $resolve  = Mockery::mock(ResolveInfo::class);
        $resolver = Mockery::spy(static function (): void {
            // empty
        });
        $cache    = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('isQueryLockable')
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('isLocked')
            ->once()
            ->andReturn(true);

        $directive = Mockery::mock(Cached::class, [$cache]);
        $directive->shouldAllowMockingProtectedMethods();
        $directive->makePartial();
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::threshold());
        $directive
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(
                new CachedValue(Date::now(), Date::now(), null, $value),
            );

        self::assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveNotCachedNotLockable(): void {
        $key      = new CacheKey([$this->faker->word()]);
        $value    = $this->faker->sentence();
        $root     = null;
        $args     = [];
        $context  = Mockery::mock(GraphQLContext::class);
        $resolve  = Mockery::mock(ResolveInfo::class);
        $resolver = Mockery::spy(static function () use ($value): mixed {
            return $value;
        });
        $cache    = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('isQuerySlow')
            ->once()
            ->andReturn(true);

        $directive = Mockery::mock(Cached::class, [$cache]);
        $directive->shouldAllowMockingProtectedMethods();
        $directive->makePartial();
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::threshold());
        $directive
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(null);
        $directive
            ->shouldReceive('setCachedValue')
            ->with($key, $value, Mockery::any())
            ->once()
            ->andReturn($value);

        self::assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldHaveBeenCalled()
            ->once();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveNotCachedLockable(): void {
        $key      = new CacheKey([$this->faker->word()]);
        $value    = $this->faker->sentence();
        $root     = null;
        $args     = [];
        $context  = Mockery::mock(GraphQLContext::class);
        $resolve  = Mockery::mock(ResolveInfo::class);
        $resolver = Mockery::spy(static function (): void {
            // empty
        });
        $cache    = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('isQueryWasLocked')
            ->once()
            ->andReturn(false);
        $cache
            ->shouldReceive('lock')
            ->with($key, Mockery::any())
            ->once()
            ->andReturnUsing(static function (mixed $key, Closure $resolver): mixed {
                return $resolver();
            });

        $directive = Mockery::mock(Cached::class, [$cache]);
        $directive->shouldAllowMockingProtectedMethods();
        $directive->makePartial();
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::normal());
        $directive
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(null);
        $directive
            ->shouldReceive('setCachedValue')
            ->once()
            ->andReturn($value);

        self::assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldHaveBeenCalled()
            ->once();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveNotCachedLockableWasLockedButNotUpdatedInAnotherThread(): void {
        $key      = new CacheKey([$this->faker->word()]);
        $value    = $this->faker->sentence();
        $root     = null;
        $args     = [];
        $context  = Mockery::mock(GraphQLContext::class);
        $resolve  = Mockery::mock(ResolveInfo::class);
        $resolver = Mockery::spy(static function () use ($value): mixed {
            return $value;
        });
        $cache    = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('isQueryWasLocked')
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('lock')
            ->with($key, Mockery::any())
            ->once()
            ->andReturnUsing(static function (mixed $key, Closure $resolver): mixed {
                return $resolver();
            });

        $directive = Mockery::mock(Cached::class, [$cache]);
        $directive->shouldAllowMockingProtectedMethods();
        $directive->makePartial();
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::normal());
        $directive
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(null);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(
                new CachedValue(Date::now(), Date::now(), null, $value),
            );
        $directive
            ->shouldReceive('setCachedValue')
            ->once()
            ->andReturn($value);

        self::assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldHaveBeenCalled()
            ->once();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveNotCachedLockableWasLockedAndUpdatedInAnotherThread(): void {
        $key      = new CacheKey([$this->faker->word()]);
        $value    = $this->faker->sentence();
        $root     = null;
        $args     = [];
        $context  = Mockery::mock(GraphQLContext::class);
        $resolve  = Mockery::mock(ResolveInfo::class);
        $resolver = Mockery::spy(static function (): void {
            // empty
        });
        $cache    = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(false);
        $cache
            ->shouldReceive('isQueryWasLocked')
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('lock')
            ->with($key, Mockery::any())
            ->once()
            ->andReturnUsing(static function (mixed $key, Closure $resolver): mixed {
                return $resolver();
            });

        $directive = Mockery::mock(Cached::class, [$cache]);
        $directive->shouldAllowMockingProtectedMethods();
        $directive->makePartial();
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::normal());
        $directive
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(null);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(
                new CachedValue(Date::now(), Date::now(), null, $value),
            );

        self::assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveCachedExpiredThreshold(): void {
        $key      = new CacheKey([$this->faker->word()]);
        $value    = $this->faker->sentence();
        $root     = null;
        $args     = [];
        $context  = Mockery::mock(GraphQLContext::class);
        $resolve  = Mockery::mock(ResolveInfo::class);
        $resolver = Mockery::spy(static function () use ($value): mixed {
            return $value;
        });
        $cache    = Mockery::mock(Cache::class);
        $cache
            ->shouldReceive('isExpired')
            ->once()
            ->andReturn(true);
        $cache
            ->shouldReceive('isQueryLockable')
            ->once()
            ->andReturn(false);
        $cache
            ->shouldReceive('isQuerySlow')
            ->once()
            ->andReturn(false);

        $directive = Mockery::mock(Cached::class, [$cache]);
        $directive->shouldAllowMockingProtectedMethods();
        $directive->makePartial();
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::threshold());
        $directive
            ->shouldReceive('getCacheKey')
            ->once()
            ->andReturn($key);
        $directive
            ->shouldReceive('getCachedValue')
            ->with($key)
            ->once()
            ->andReturn(
                new CachedValue(Date::now(), Date::now(), null, null),
            );
        $directive
            ->shouldReceive('deleteCachedValue')
            ->with($key)
            ->once()
            ->andReturn(true);

        self::assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldHaveBeenCalled()
            ->once();
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderHandleField(): array {
        $id           = '7c632aef-38fe-48fd-bf88-2097994a194c';
        $model        = addslashes(Organization::class);
        $organization = static function () use ($id): Organization {
            return Organization::factory()->create([
                'id' => $id,
            ]);
        };

        return [
            'root (without args)'    => [
                new GraphQLSuccess('root', json_encode('value', JSON_THROW_ON_ERROR)),
                $organization,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    root: String @cached @mock
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    root
                }
                GRAPHQL,
            ],
            'aggregated (with root)' => [
                new GraphQLSuccess('models', [
                    [
                        'aggregated' => [
                            'count' => 'value',
                        ],
                    ],
                ]),
                $organization,
                /** @lang GraphQL */ <<<GRAPHQL
                type Query {
                    models: [Organization!]! @all
                }

                type Organization {
                    aggregated(where: AggregatedQuery @searchBy): Aggregated
                    @aggregated(
                        model: "{$model}"
                    )
                }

                type Aggregated {
                    count: String! @cached @mock
                }

                input AggregatedQuery {
                    id: ID!
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    models {
                        aggregated(where: {id: {notEqual: "123"}}) {
                            count
                        }
                    }
                }
                GRAPHQL,
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderGetCacheKey(): array {
        $id       = '6cb71725-be72-4a13-832f-65b57d63fb23';
        $model    = addslashes(Organization::class);
        $cacheKey = "Organization:{$id}";
        $factory  = static function () use ($id): Organization {
            return Organization::factory()->create([
                'id' => $id,
            ]);
        };

        return [
            'root (without args)'       => [
                new GraphQLSuccess('root', json_encode((string) new CacheKey([
                    '',
                    'root',
                    '',
                    '@cached',
                ]))),
                $factory,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    root: String @cached
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    root
                }
                GRAPHQL,
            ],
            'root (with args)'          => [
                new GraphQLSuccess('root', json_encode((string) new CacheKey([
                    '',
                    'root',
                    [
                        'param' => ['value'],
                        'where' => ['id' => ['equal' => '123']],
                    ],
                    '@cached',
                ]))),
                $factory,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    root(param: [String!], where: RootQuery @searchBy): String @cached
                }

                input RootQuery {
                    id: ID!
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    root(where: {id: {equal: "123"}}, param: ["value"])
                }
                GRAPHQL,
            ],
            'models'                    => [
                new GraphQLSuccess('models', [
                    [
                        'value' => (string) new CacheKey([
                            $cacheKey,
                            'value',
                            '',
                            '@cached',
                        ]),
                    ],
                ]),
                $factory,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    models(where: OrganizationsQuery @searchBy): [Organization!]! @all
                }

                type Organization {
                    id: ID!
                    value: String @cached
                }

                input OrganizationsQuery {
                    id: ID!
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    models(where: {id: {notEqual: "123"}}) {
                        value
                    }
                }
                GRAPHQL,
            ],
            'aggregated (without root)' => [
                new GraphQLSuccess('aggregated', [
                    'count' => (string) new CacheKey([
                        '',
                        'aggregated',
                        [
                            'where' => ['id' => ['notEqual' => '123']],
                        ],
                        'count',
                        '',
                        '@cached',
                    ]),
                ]),
                $factory,
                /** @lang GraphQL */ <<<GRAPHQL
                type Query {
                    aggregated(where: AggregatedQuery @searchBy): Aggregated
                    @aggregated(
                        model: "{$model}"
                    )
                }

                type Aggregated {
                    count: String! @cached
                }

                input AggregatedQuery {
                    id: ID!
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    aggregated(where: {id: {notEqual: "123"}}) {
                        count
                    }
                }
                GRAPHQL,
            ],
            'aggregated (with root)'    => [
                new GraphQLSuccess('models', [
                    [
                        'aggregated' => [
                            'count' => (string) new CacheKey([
                                $cacheKey,
                                'aggregated',
                                [
                                    'where' => ['id' => ['notEqual' => '123']],
                                ],
                                'count',
                                '',
                                '@cached',
                            ]),
                        ],
                    ],
                ]),
                $factory,
                /** @lang GraphQL */ <<<GRAPHQL
                type Query {
                    models: [Organization!]! @all
                }

                type Organization {
                    aggregated(where: AggregatedQuery @searchBy): Aggregated
                    @aggregated(
                        model: "{$model}"
                    )
                }

                type Aggregated {
                    count: String! @cached
                }

                input AggregatedQuery {
                    id: ID!
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    models {
                        aggregated(where: {id: {notEqual: "123"}}) {
                            count
                        }
                    }
                }
                GRAPHQL,
            ],
        ];
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function dataProviderGetResolveMode(): array {
        $id           = 'b6369c49-6f20-4e05-8f5d-98d06bf871e6';
        $organization = static function () use ($id): Organization {
            return Organization::factory()->create([
                'id' => $id,
            ]);
        };

        return [
            'default (root)'                 => [
                new GraphQLSuccess('root', CachedMode::normal()),
                $organization,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    root: String @cached
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    root
                }
                GRAPHQL,
            ],
            'default (nested)'               => [
                new GraphQLSuccess('root', [
                    'id' => CachedMode::normal(),
                ]),
                $organization,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    root: Organization @all
                }

                type Organization {
                    id: ID! @cached
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    root {
                        id
                    }
                }
                GRAPHQL,
            ],
            (string) CachedMode::normal()    => [
                new GraphQLSuccess('root', CachedMode::normal()),
                $organization,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    root: String @cached(mode: Normal)
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    root
                }
                GRAPHQL,
            ],
            (string) CachedMode::threshold() => [
                new GraphQLSuccess('root', CachedMode::threshold()),
                $organization,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    root: String @cached(mode: Threshold)
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    root
                }
                GRAPHQL,
            ],
        ];
    }
    // </editor-fold>
}
