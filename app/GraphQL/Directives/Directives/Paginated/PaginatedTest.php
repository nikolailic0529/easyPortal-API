<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\Models\Asset;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use Tests\GraphQL\Schemas\AnySchema;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutGlobalScopes;
use Tests\WithSettings;

use function json_encode;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Paginated\Paginated
 * @covers \App\GraphQL\Directives\Directives\Paginated\Manipulator
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class PaginatedTest extends TestCase {
    use WithoutGlobalScopes;
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderManipulateArgDefinition
     *
     * @param SettingsFactory $settingsFactory
     */
    public function testManipulateArgDefinition(string $expected, mixed $settingsFactory): void {
        $this->setSettings($settingsFactory);

        self::assertGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected($expected, '~schema.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

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
                    key: String
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
                    key: String
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
                    key: String
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
