<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\Models\Customer;
use App\Models\CustomerLocation;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use LogicException;
use Tests\GraphQL\Schemas\AnySchema;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutGlobalScopes;
use Tests\WithSettings;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Paginated\PaginatedRelation
 *
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class PaginatedRelationTest extends TestCase {
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

    public function testManipulateArgDefinitionNoModel(): void {
        self::expectExceptionObject(new LogicException(
            '@paginatedRelation directive should be used with one of `@relation`.',
        ));

        $this->getGraphQLSchema(
            /** @lang GraphQL */
            <<<'GRAPHQL'
            type Query {
                query: [Customer!]! @all
            }

            type Customer {
                locations: [CustomerLocation!] @paginatedRelation
            }

            type CustomerLocation {
                id: ID!
            }
            GRAPHQL,
        );
    }

    /**
     * @coversNothing
     */
    public function testResolveFieldModel(): void {
        $customer = Customer::factory()->create();

        CustomerLocation::factory()->create([
            'customer_id' => $customer,
        ]);
        CustomerLocation::factory()->create([
            'customer_id' => $customer,
        ]);

        $this
            ->useGraphQLSchema(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                type Query {
                    customers: [Customer!]! @all
                }

                type Customer {
                    locations: [CustomerLocation!]
                    @relation
                    @paginatedRelation
                }

                type CustomerLocation {
                    id: ID!
                    key: String
                }
                GRAPHQL,
            )
            ->graphQL(
                /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    customers {
                        locations(limit: 1) {
                            id
                        }
                        locationsAggregated {
                            count
                        }
                    }
                }
                GRAPHQL,
            )
            ->assertThat(new OkResponse(AnySchema::class, [
                'data' => [
                    'customers' => [
                        [
                            'locations'           => [
                                [
                                    'id' => $customer->locations->sortBy('id')->first()?->getKey(),
                                ],
                            ],
                            'locationsAggregated' => [
                                'count' => 2,
                            ],
                        ],
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
            // 'no model' => [],
        ];
    }
    //</editor-fold>
}
