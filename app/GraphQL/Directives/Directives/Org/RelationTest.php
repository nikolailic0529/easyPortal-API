<?php declare(strict_types = 1);

namespace App\GraphQL\Directives\Directives\Org;

use App\Models\Customer;
use App\Models\Kpi;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use PHPUnit\Framework\Constraint\Constraint;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithGraphQLSchema;
use Tests\WithOrganization;

/**
 * @internal
 * @covers \App\GraphQL\Directives\Directives\Org\Relation
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 */
class RelationTest extends TestCase {
    use WithGraphQLSchema;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderResolveField
     *
     * @param OrganizationFactory $orgFactory
     */
    public function testResolveField(
        Constraint $expected,
        mixed $orgFactory,
        Closure $factory,
    ): void {
        $organization = $this->setOrganization($orgFactory);

        $factory($this, $organization);

        $this
            ->useGraphQLSchema(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                type Query {
                    customers: [Customer!]! @all
                }

                type Customer {
                    id: ID!
                    kpi: Kpi! @orgRelation
                }

                type Kpi {
                    assets_total: Int!
                }
                GRAPHQL,
            )
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query {
                    customers {
                        id
                        kpi {
                            assets_total
                        }
                    }
                }
                GRAPHQL,
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function dataProviderResolveField(): array {
        $customerId = '56a21b9e-65c1-4d99-acfa-500936e8f68b';
        $rootValue  = 321;
        $orgValue   = 123;
        $factory    = static function (
            self $test,
            Organization $organization,
        ) use (
            $customerId,
            $rootValue,
            $orgValue,
        ): void {
            $rootKpi  = Kpi::factory()->create([
                'assets_total' => $rootValue,
            ]);
            $orgKpi   = Kpi::factory()->create([
                'assets_total' => $orgValue,
            ]);
            $reseller = Reseller::factory()->create(['id' => $organization]);
            $customer = Customer::factory()->create([
                'id'     => $customerId,
                'kpi_id' => $rootKpi,
            ]);

            $customer->resellers()->attach($reseller, [
                'kpi_id' => $orgKpi->getKey(),
            ]);
        };

        return [
            'root organization' => [
                new GraphQLSuccess('customers', [
                    [
                        'id'  => $customerId,
                        'kpi' => [
                            'assets_total' => $rootValue,
                        ],
                    ],
                ]),
                static function (self $test): Organization {
                    return $test->setRootOrganization(Organization::factory()->create());
                },
                $factory,
            ],
            'organization'      => [
                new GraphQLSuccess('customers', [
                    [
                        'id'  => $customerId,
                        'kpi' => [
                            'assets_total' => $orgValue,
                        ],
                    ],
                ]),
                static function (self $test): Organization {
                    return Organization::factory()->create();
                },
                $factory,
            ],
        ];
    }
    // </editor-fold>
}
