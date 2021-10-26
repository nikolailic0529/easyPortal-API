<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Directives\Definitions\PaginatedDirective;
use App\GraphQL\Queries\Customers\Customer;
use App\Models\Asset;
use App\Services\Search\Builders\Builder as SearchBuilder;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Tests\DataProviders\Builders\BuilderDataProvider;
use Tests\GraphQL\Schemas\AnySchema;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutOrganizationScope;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Paginated\Paginated
 */
class PaginatedTest extends TestCase {
    use WithoutOrganizationScope;
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::manipulateArgDefinition
     * @covers \App\GraphQL\Directives\Directives\Paginated\Manipulator::update
     * @covers \App\GraphQL\Directives\Directives\Paginated\Manipulator::getLimitField
     * @covers \App\GraphQL\Directives\Directives\Paginated\Manipulator::getOffsetField
     *
     * @dataProvider dataProviderManipulateArgDefinition
     *
     * @param array<string, mixed> $settings
     */
    public function testManipulateArgDefinition(string $expected, array $settings): void {
        $this->setSettings($settings);

        $this->assertGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected($expected, '~schema.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

    /**
     * @covers ::handleBuilder
     *
     * @dataProvider dataProviderHandleBuilder
     *
     * @param array{query: string, bindings: array<mixed>} $expected
     * @param array<mixed>                                 $args
     */
    public function testHandleBuilder(
        array $expected,
        Closure $builder,
        array $args,
    ): void {
        $directive = $this->app->make(PaginatedDirective::class);
        $builder   = $builder($this);
        $builder   = $directive->handleBuilder($builder, $args);

        $this->assertDatabaseQueryEquals($expected, $builder);
    }

    /**
     * @covers ::handleScoutBuilder
     *
     * @dataProvider dataProviderHandleScoutBuilder
     *
     * @param array{limit: ?int, offset: int} $expected
     * @param array<mixed>                    $args
     */
    public function testHandleScoutBuilder(array $expected, array $args): void {
        $directive = $this->app->make(PaginatedDirective::class);
        $builder   = $this->app->make(SearchBuilder::class, [
            'query' => '123',
            'model' => new class() extends Model {
                // empty
            },
        ]);
        $builder   = $directive->handleScoutBuilder($builder, $args);

        $this->assertEquals($expected, [
            'limit'  => $builder->limit,
            'offset' => $builder->offset ?? null,
        ]);
    }

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldNoArguments(): void {
        $asset = Asset::factory()->create();

        $this->setSettings([
            'ep.pagination.limit.default' => 25,
        ]);

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                type Query {
                    data: [Asset!]! @paginated
                }

                type Asset {
                    id: ID!
                }
                GRAPHQL,
            )
            ->graphQL(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    data {
                        id
                    }
                    dataAggregated {
                        count
                    }
                }
                GRAPHQL,
            )
            ->assertThat(new OkResponse(AnySchema::class, [
                'data' => [
                    'data'           => [
                        [
                            'id' => $asset->getKey(),
                        ],
                    ],
                    'dataAggregated' => [
                        'count' => 1,
                    ],
                ],
            ]));
    }

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldBuilder(): void {
        $asset   = Asset::factory()->create();
        $model   = json_encode(Customer::class);
        $builder = json_encode(PaginatedTest_Resolver::class);

        $this->setSettings([
            'ep.pagination.limit.default' => 25,
        ]);

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    data: [Model!]! @paginated(
                        model: {$model}
                        builder: {$builder}
                    )
                }

                type Model {
                    id: ID!
                }
                GRAPHQL,
            )
            ->graphQL(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    data {
                        id
                    }
                    dataAggregated {
                        count
                    }
                }
                GRAPHQL,
            )
            ->assertThat(new OkResponse(AnySchema::class, [
                'data' => [
                    'data'           => [
                        [
                            'id' => $asset->getKey(),
                        ],
                    ],
                    'dataAggregated' => [
                        'count' => 1,
                    ],
                ],
            ]));
    }

    /**
     * @covers ::resolveField
     */
    public function testResolveFieldModel(): void {
        $asset = Asset::factory()->create();
        $model = json_encode($asset::class);

        $this->setSettings([
            'ep.pagination.limit.default' => 25,
        ]);

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<GRAPHQL
                type Query {
                    data: [Model!]! @paginated(
                        model: {$model}
                    )
                }

                type Model {
                    id: ID!
                }
                GRAPHQL,
            )
            ->graphQL(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    data {
                        id
                    }
                    dataAggregated {
                        count
                    }
                }
                GRAPHQL,
            )
            ->assertThat(new OkResponse(AnySchema::class, [
                'data' => [
                    'data'           => [
                        [
                            'id' => $asset->getKey(),
                        ],
                    ],
                    'dataAggregated' => [
                        'count' => 1,
                    ],
                ],
            ]));
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderManipulateArgDefinition(): array {
        return [
            'with limit'    => [
                '~expected-with-limit.graphql',
                [
                    'ep.pagination.limit.default' => 25,
                    'ep.pagination.limit.max'     => 123,
                ],
            ],
            'without limit' => [
                '~expected-without-limit.graphql',
                [
                    'ep.pagination.limit.default' => null,
                    'ep.pagination.limit.max'     => 321,
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleBuilder(): array {
        return (new CompositeDataProvider(
            new BuilderDataProvider(),
            new ArrayDataProvider([
                'limit + offset' => [
                    [
                        'query'    => 'select * from `tmp` limit 123 offset 45',
                        'bindings' => [],
                    ],
                    [
                        'limit'  => 123,
                        'offset' => 45,
                    ],
                ],
                'limit'          => [
                    [
                        'query'    => 'select * from `tmp` limit 123',
                        'bindings' => [],
                    ],
                    [
                        'limit' => 123,
                    ],
                ],
                'offset'         => [
                    [
                        'query'    => 'select * from `tmp`',
                        'bindings' => [],
                    ],
                    [
                        'offset' => 123,
                    ],
                ],
                'none'           => [
                    [
                        'query'    => 'select * from `tmp`',
                        'bindings' => [],
                    ],
                    [
                        // empty
                    ],
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<string,mixed>
     */
    public function dataProviderHandleScoutBuilder(): array {
        return [
            'limit + offset' => [
                [
                    'limit'  => 123,
                    'offset' => 45,
                ],
                [
                    'limit'  => 123,
                    'offset' => 45,
                ],
            ],
            'limit'          => [
                [
                    'limit'  => 123,
                    'offset' => null,
                ],
                [
                    'limit' => 123,
                ],
            ],
            'offset'         => [
                [
                    'limit'  => null,
                    'offset' => null,
                ],
                [
                    'offset' => 123,
                ],
            ],
            'none'           => [
                [
                    'limit'  => null,
                    'offset' => null,
                ],
                [
                    // empty
                ],
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
class PaginatedTest_Resolver {
    public function __invoke(): EloquentBuilder {
        return Asset::query();
    }
}
