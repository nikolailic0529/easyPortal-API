<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Cached;

use App\Models\Organization;
use App\Services\Organization\CurrentOrganization;
use App\Utils\CacheKey;
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
        Closure $organizationFactory,
        string $schema,
        string $graphql,
    ): void {
        $this->setOrganization($organizationFactory);

        $provider  = $this->app->make(CurrentOrganization::class);
        $directive = new class($provider) extends Cached implements FieldResolver {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(
                protected CurrentOrganization $organization,
            ) {
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
        $id           = '6cb71725-be72-4a13-832f-65b57d63fb23';
        $model        = addslashes(Organization::class);
        $cacheKey     = "Organization:{$id}";
        $organization = static function () use ($id): Organization {
            return Organization::factory()->create([
                'id' => $id,
            ]);
        };

        return [
            'root (without args)'       => [
                new GraphQLSuccess('root', null, json_encode(sprintf(
                    '%s::root::@cached',
                    $cacheKey,
                ))),
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
            'root (with args)'          => [
                new GraphQLSuccess('root', null, json_encode(sprintf(
                    '%s::root:%s:@cached',
                    $cacheKey,
                    json_encode([
                        'param' => ['value'],
                        'where' => ['id' => ['equal' => '123']],
                    ]),
                ))),
                $organization,
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
                            '%s:%s:value::@cached',
                            $cacheKey,
                            $cacheKey,
                        ),
                    ],
                ]),
                $organization,
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
                        '%s::aggregated:%s:count::@cached',
                        $cacheKey,
                        json_encode([
                            'where' => ['id' => ['notEqual' => '123']],
                        ]),
                    ),
                ]),
                $organization,
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
                                '%s:%s:aggregated:%s:count::@cached',
                                $cacheKey,
                                $cacheKey,
                                json_encode([
                                    'where' => ['id' => ['notEqual' => '123']],
                                ]),
                            ),
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
    // </editor-fold>
}
