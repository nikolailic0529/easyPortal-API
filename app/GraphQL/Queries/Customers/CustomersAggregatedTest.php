<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Customers\CustomersAggregated
 */
class CustomersAggregatedTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::assets
     *
     * @dataProvider dataProviderQuery
     *
     * @param array<mixed> $params
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $factory = null,
        array $params = [],
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($factory) {
            $factory($this, $organization, $user);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query ($where: SearchByConditionCustomersQuery) {
                    customersAggregated(where: $where) {
                        count
                        assets
                    }
                }
                GRAPHQL,
                $params + [
                    'where' => [],
                ],
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        $params  = [
            'where' => [
                'contacts_count' => [
                    'lessThanOrEqual' => 2,
                ],
            ],
        ];
        $factory = static function (TestCase $test, Organization $organization): void {
            // Customers
            $customerA = Customer::factory()->create([
                'assets_count'   => 1,
                'contacts_count' => 1,
            ]);
            $customerB = Customer::factory()->create([
                'assets_count'   => 2,
                'contacts_count' => 2,
            ]);

            Customer::factory()->create([
                'assets_count'   => 3,
                'contacts_count' => 3,
            ]);

            // Resellers
            $resellerA = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $resellerB = Reseller::factory()->create();

            $resellerA->customers()->attach($customerA);
            $resellerB->customers()->attach($customerB);

            Asset::factory()->create([
                'reseller_id' => $resellerA,
                'customer_id' => $customerA,
            ]);
        };

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new AuthOrgRootDataProvider('customersAggregated'),
                new OrgUserDataProvider('customersAggregated', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customersAggregated', self::class, [
                            'count'  => 2,
                            'assets' => 3,
                        ]),
                        $factory,
                        $params,
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('customersAggregated'),
                new OrgUserDataProvider('customersAggregated', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customersAggregated', self::class, [
                            'count'  => 1,
                            'assets' => 1,
                        ]),
                        $factory,
                        $params,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
