<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
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
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Map
 */
class MapTest extends TestCase {
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderQuery
     *
     * @param array{where: array<mixed>, diff: float} $params
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
                query ($where: SearchByConditionMapQuery, $diff: Float!) {
                    map (where: $where, diff: $diff) {
                        latitude_avg
                        latitude_min
                        latitude_max
                        longitude_avg
                        longitude_min
                        longitude_max
                        customers_count
                        assets_count
                        customers_ids
                    }
                }
                GRAPHQL,
                $params + [
                    'where' => [],
                    'diff'  => 0.0000001,
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
            'diff'  => 0.25,
            'where' => [
                'allOf' => [
                    [
                        'latitude' => [
                            'between' => [
                                'min' => 0,
                                'max' => 10,
                            ],
                        ],
                    ],
                    [
                        'longitude' => [
                            'between' => [
                                'min' => 0,
                                'max' => 10,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $factory = static function (TestCase $test, Organization $organization): void {
            // Customers
            $customerA = Customer::factory()->create([
                'id' => 'ad16444a-46a4-3036-b893-7636e2e6209b',
            ]);
            $customerB = Customer::factory()->create([
                'id' => 'ad16444a-46a4-3036-b893-7636e2e6209c',
            ]);

            // Resellers
            $resellerA = Reseller::factory()->make([
                'id' => $organization->getKey(),
            ]);
            $resellerB = Reseller::factory()->make();

            $resellerA->customers = [$customerA];
            $resellerB->customers = [$customerB];

            $resellerA->save();
            $resellerB->save();

            // Inside
            $locationA = Location::factory()->create([
                'latitude'  => 1.00,
                'longitude' => 1.00,
            ]);
            $locationB = Location::factory()->create([
                'latitude'  => 1.10,
                'longitude' => 1.10,
                'object_id' => $customerA,
            ]);
            $locationC = Location::factory()->create([
                'latitude'    => 1.5,
                'longitude'   => 1.5,
                'object_type' => (new Asset())->getMorphClass(),
                'object_id'   => null,
            ]);
            Location::factory()->create([
                'latitude'  => 1.25,
                'longitude' => 1.25,
                'object_id' => $customerB,
            ]);

            Asset::factory()->create([
                'location_id' => $locationA,
                'reseller_id' => $resellerA,
            ]);
            Asset::factory()->create([
                'location_id' => $locationA,
                'reseller_id' => $resellerA,
            ]);
            Asset::factory()->create([
                'location_id' => $locationA,
                'reseller_id' => $resellerB,
            ]);
            Asset::factory()->create([
                'location_id' => $locationB,
                'reseller_id' => $resellerA,
            ]);
            Asset::factory()->create([
                'location_id' => $locationC,
                'reseller_id' => $resellerA,
            ]);

            // Empty
            Location::factory()->create([
                'latitude'  => 2,
                'longitude' => 2,
            ]);

            // Outside
            Location::factory()->create([
                'latitude'  => -1.00,
                'longitude' => 1.00,
            ]);
            Location::factory()->create([
                'latitude'  => 1.00,
                'longitude' => -1.00,
            ]);
        };

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('map'),
                new OrganizationUserDataProvider('map', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('map', self::class, [
                            [
                                'latitude_avg'    => 1.025,
                                'latitude_min'    => 1,
                                'latitude_max'    => 1.1,
                                'longitude_avg'   => 1.025,
                                'longitude_min'   => 1,
                                'longitude_max'   => 1.1,
                                'customers_count' => 1,
                                'assets_count'    => 4,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                            ],
                            [
                                'latitude_avg'    => 1.25,
                                'latitude_min'    => 1.25,
                                'latitude_max'    => 1.25,
                                'longitude_avg'   => 1.25,
                                'longitude_min'   => 1.25,
                                'longitude_max'   => 1.25,
                                'customers_count' => 1,
                                'assets_count'    => 0,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209c',
                                ],
                            ],
                            [
                                'latitude_avg'    => 1.5,
                                'latitude_min'    => 1.5,
                                'latitude_max'    => 1.5,
                                'longitude_avg'   => 1.5,
                                'longitude_min'   => 1.5,
                                'longitude_max'   => 1.5,
                                'customers_count' => 0,
                                'assets_count'    => 1,
                                'customers_ids'   => [],
                            ],
                        ]),
                        $factory,
                        $params,
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('map'),
                new OrganizationUserDataProvider('map', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('map', self::class, [
                            [
                                'latitude_avg'    => 1.033333333333,
                                'latitude_min'    => 1,
                                'latitude_max'    => 1.1,
                                'longitude_avg'   => 1.033333333333,
                                'longitude_min'   => 1,
                                'longitude_max'   => 1.1,
                                'customers_count' => 1,
                                'assets_count'    => 3,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                            ],
                            [
                                'latitude_avg'    => 1.5,
                                'latitude_min'    => 1.5,
                                'latitude_max'    => 1.5,
                                'longitude_avg'   => 1.5,
                                'longitude_min'   => 1.5,
                                'longitude_max'   => 1.5,
                                'customers_count' => 0,
                                'assets_count'    => 1,
                                'customers_ids'   => [],
                            ],
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
