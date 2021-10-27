<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\GraphQL\Queries\Customers\Customer;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
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
    //</editor-fold>

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
    //</editor-fold>
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