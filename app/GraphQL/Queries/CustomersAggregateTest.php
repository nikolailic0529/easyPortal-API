<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\CustomersAggregate
 */
class CustomersAggregateTest extends TestCase {
    /**
     * @covers ::__invoke
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
                    customersAggregate(where: $where) {
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
                'assets_count' => [
                    'lte' => 2,
                ],
            ],
        ];
        $factory = static function (TestCase $test, Organization $organization): void {
            // Customers
            $customerA = Customer::factory()->create([
                'assets_count' => 1,
            ]);
            $customerB = Customer::factory()->create([
                'assets_count' => 2,
            ]);

            Customer::factory()->create([
                'assets_count' => 3,
            ]);

            // Resellers
            $resellerA = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $resellerB = Reseller::factory()->create();

            $resellerA->customers = [$customerA];
            $resellerB->customers = [$customerB];
        };

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('customersAggregate'),
                new OrganizationUserDataProvider('customersAggregate', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customersAggregate', self::class, [
                            'count'  => 2,
                            'assets' => 3,
                        ]),
                        $factory,
                        $params,
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('customersAggregate'),
                new UserDataProvider('customersAggregate', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customersAggregate', self::class, [
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
