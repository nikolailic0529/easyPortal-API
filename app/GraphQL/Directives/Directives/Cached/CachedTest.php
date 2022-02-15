<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use App\Models\Organization;
use App\Utils\Cache\CacheKey;
use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use Mockery;
use Nuwave\Lighthouse\Schema\DirectiveLocator;
use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldResolver;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;

use function addslashes;
use function json_encode;
use function sprintf;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Cached\Cached
 */
class CachedTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::handleField
     *
     * @dataProvider dataProviderHandleField
     */
    public function testResolveField(
        Response $expected,
        Closure $organizationFactory,
        string $schema,
        string $graphql,
    ): void {
        $this->setOrganization($organizationFactory);
        $this->setSettings([
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
                        return (string) new class(
                            $this->getCacheKey($root, $args, $context, $resolveInfo),
                        ) extends CacheKey {
                            protected function hash(string $value): string {
                                return $value;
                            }
                        };
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
     */
    public function testGetResolveMode(
        Response $expected,
        Closure $organizationFactory,
        string $schema,
        string $graphql,
    ): void {
        $this->setOrganization($organizationFactory);

        $directive = new class() extends Cached implements FieldResolver {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty;
            }

            public function handleField(FieldValue $fieldValue, Closure $next): FieldValue {
                return $next($fieldValue);
            }

            public function resolveField(FieldValue $fieldValue): FieldValue {
                return $fieldValue->setResolver(function (mixed $root): string {
                    return (string) $this->getResolveMode($root);
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
    public function testResolveNotCachedModeLock(): void {
        $key       = $this->faker->word;
        $value     = $this->faker->sentence;
        $root      = null;
        $args      = [];
        $context   = Mockery::mock(GraphQLContext::class);
        $resolve   = Mockery::mock(ResolveInfo::class);
        $resolver  = Mockery::spy(static function (): void {
            // empty
        });
        $directive = Mockery::mock(Cached::class);
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
            ->andReturn([false, false, null]);
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::lock());
        $directive
            ->shouldReceive('resolveWithLock')
            ->once()
            ->with(
                $key,
                $resolver,
                $root,
                $args,
                $context,
                $resolve,
            )
            ->andReturn($value);

        $this->assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveNotCachedModeThreshold(): void {
        $key       = $this->faker->word;
        $value     = $this->faker->sentence;
        $root      = null;
        $args      = [];
        $context   = Mockery::mock(GraphQLContext::class);
        $resolve   = Mockery::mock(ResolveInfo::class);
        $resolver  = Mockery::spy(static function (): void {
            // empty
        });
        $directive = Mockery::mock(Cached::class);
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
            ->andReturn([false, false, null]);
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::threshold());
        $directive
            ->shouldReceive('resolveWithThreshold')
            ->once()
            ->with(
                $key,
                $resolver,
                $root,
                $args,
                $context,
                $resolve,
            )
            ->andReturn($value);

        $this->assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveCachedAndNotExpired(): void {
        $key       = $this->faker->word;
        $value     = $this->faker->sentence;
        $root      = null;
        $args      = [];
        $context   = Mockery::mock(GraphQLContext::class);
        $resolve   = Mockery::mock(ResolveInfo::class);
        $resolver  = Mockery::spy(static function (): void {
            // empty
        });
        $directive = Mockery::mock(Cached::class);
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
            ->andReturn([true, false, $value]);
        $directive
            ->shouldReceive('getResolveMode')
            ->never();
        $directive
            ->shouldReceive('resolveWithLock')
            ->never();
        $directive
            ->shouldReceive('resolveWithThreshold')
            ->never();

        $this->assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveCachedExpiredModeThreshold(): void {
        $key       = $this->faker->word;
        $value     = $this->faker->sentence;
        $root      = null;
        $args      = [];
        $context   = Mockery::mock(GraphQLContext::class);
        $resolve   = Mockery::mock(ResolveInfo::class);
        $resolver  = Mockery::spy(static function (): void {
            // empty
        });
        $directive = Mockery::mock(Cached::class);
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
            ->andReturn([true, true, null]);
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::threshold());
        $directive
            ->shouldReceive('resolveWithThreshold')
            ->once()
            ->with(
                $key,
                $resolver,
                $root,
                $args,
                $context,
                $resolve,
            )
            ->andReturn($value);

        $this->assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveCachedExpiredModeLockNotLocked(): void {
        $key       = $this->faker->word;
        $value     = $this->faker->sentence;
        $root      = null;
        $args      = [];
        $context   = Mockery::mock(GraphQLContext::class);
        $resolve   = Mockery::mock(ResolveInfo::class);
        $resolver  = Mockery::spy(static function (): void {
            // empty
        });
        $directive = Mockery::mock(Cached::class);
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
            ->andReturn([true, true, null]);
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::lock());
        $directive
            ->shouldReceive('resolveIsLocked')
            ->once()
            ->andReturn(false);
        $directive
            ->shouldReceive('resolveWithLock')
            ->once()
            ->with(
                $key,
                $resolver,
                $root,
                $args,
                $context,
                $resolve,
            )
            ->andReturn($value);

        $this->assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
    }

    /**
     * @covers ::resolve
     */
    public function testResolveCachedExpiredModeLockLocked(): void {
        $key       = $this->faker->word;
        $value     = $this->faker->sentence;
        $root      = null;
        $args      = [];
        $context   = Mockery::mock(GraphQLContext::class);
        $resolve   = Mockery::mock(ResolveInfo::class);
        $resolver  = Mockery::spy(static function (): void {
            // empty
        });
        $directive = Mockery::mock(Cached::class);
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
            ->andReturn([true, true, $value]);
        $directive
            ->shouldReceive('getResolveMode')
            ->once()
            ->andReturn(CachedMode::lock());
        $directive
            ->shouldReceive('resolveIsLocked')
            ->once()
            ->andReturn(true);
        $directive
            ->shouldReceive('resolveWithLock')
            ->never();

        $this->assertEquals($value, $directive->resolve($resolver, $root, $args, $context, $resolve));

        $resolver
            ->shouldNotHaveBeenCalled();
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
                new GraphQLSuccess('root', null, json_encode('value')),
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
                new GraphQLSuccess('models', null, [
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
                new GraphQLSuccess('root', null, json_encode(
                    ':root::@cached',
                )),
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
                new GraphQLSuccess('root', null, json_encode(sprintf(
                    ':root:%s:@cached',
                    json_encode([
                        'param' => ['value'],
                        'where' => ['id' => ['equal' => '123']],
                    ]),
                ))),
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
                new GraphQLSuccess('models', null, [
                    [
                        'value' => sprintf(
                            '%s:value::@cached',
                            $cacheKey,
                        ),
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
                new GraphQLSuccess('aggregated', null, [
                    'count' => sprintf(
                        ':aggregated:%s:count::@cached',
                        json_encode([
                            'where' => ['id' => ['notEqual' => '123']],
                        ]),
                    ),
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
                new GraphQLSuccess('models', null, [
                    [
                        'aggregated' => [
                            'count' => sprintf(
                                '%s:aggregated:%s:count::@cached',
                                $cacheKey,
                                json_encode([
                                    'where' => ['id' => ['notEqual' => '123']],
                                ]),
                            ),
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
        $model        = addslashes(Organization::class);
        $organization = static function () use ($id): Organization {
            return Organization::factory()->create([
                'id' => $id,
            ]);
        };

        return [
            'default (root)'                      => [
                new GraphQLSuccess('root', null, CachedMode::lock()),
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
            'default (nested)'                    => [
                new GraphQLSuccess('root', null, [
                    'id' => CachedMode::threshold(),
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
            (string) CachedMode::lock()           => [
                new GraphQLSuccess('root', null, CachedMode::lock()),
                $organization,
                /** @lang GraphQL */ <<<'GRAPHQL'
                type Query {
                    root: String @cached(mode: Lock)
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    root
                }
                GRAPHQL,
            ],
            (string) CachedMode::threshold()      => [
                new GraphQLSuccess('root', null, CachedMode::threshold()),
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
            'aggregated (default) (without root)' => [
                new GraphQLSuccess('aggregated', null, [
                    'count' => CachedMode::lock(),
                ]),
                $organization,
                /** @lang GraphQL */ <<<GRAPHQL
                type Query {
                    aggregated: Aggregated
                    @aggregated(
                        model: "{$model}"
                    )
                }

                type Aggregated {
                    count: String! @cached
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    aggregated {
                        count
                    }
                }
                GRAPHQL,
            ],
            'aggregated (default) (with root)'    => [
                new GraphQLSuccess('models', null, [
                    [
                        'aggregated' => [
                            'count' => CachedMode::threshold(),
                        ],
                    ],
                ]),
                $organization,
                /** @lang GraphQL */ <<<GRAPHQL
                type Query {
                    models: [Organization!]! @all
                }

                type Organization {
                    aggregated: Aggregated
                    @aggregated(
                        model: "{$model}"
                    )
                }

                type Aggregated {
                    count: String! @cached
                }
                GRAPHQL,
                /** @lang GraphQL */ <<<'GRAPHQL'
                query {
                    models {
                        aggregated {
                            count
                        }
                    }
                }
                GRAPHQL,
            ],
        ];
    }
    // </editor-fold>
}
