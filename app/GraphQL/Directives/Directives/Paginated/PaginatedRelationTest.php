<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Paginated;

use App\Models\Customer;
use App\Models\CustomerLocation;
use LastDragon_ru\LaraASP\Testing\Responses\Laravel\Json\OkResponse;
use LogicException;
use Tests\GraphQL\Schemas\AnySchema;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithoutOrganizationScope;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Directives\Directives\Paginated\PaginatedRelation
 */
class PaginatedRelationTest extends TestCase {
    use WithoutOrganizationScope;
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::manipulateArgDefinition
     *
     * @dataProvider dataProviderManipulateArgDefinition
     *
     * @param array<string, mixed> $settings
     */
    public function testManipulateArgDefinition(string $expected, array $settings): void {
        $this->setSettings($settings);

        self::assertGraphQLSchemaEquals(
            $this->getGraphQLSchemaExpected($expected, '~schema.graphql'),
            $this->getTestData()->content('~schema.graphql'),
        );
    }

    /**
     * @covers ::manipulateArgDefinition
     */
    public function testManipulateArgDefinitionNoModel(): void {
        self::expectExceptionObject(new LogicException(
            '@paginatedRelation directive should be used with one of `@hasMany`, `@morphMany`, `@belongsToMany`.',
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
                    @hasMany
                    @paginatedRelation
                }

                type CustomerLocation {
                    id: ID!
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
                                    'id' => $customer->locations->first()?->getKey(),
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
