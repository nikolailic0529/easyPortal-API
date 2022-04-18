<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Map;

use App\Models\Asset;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Reseller;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geohash\Geohash;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function array_merge;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Map
 */
class MapTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
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
                query (
                    $level: Int!,
                    $boundaries: [Geohash!]
                    $locations: SearchByConditionMapQuery
                    $assets: SearchByConditionAssetsQuery
                ) {
                    map (level: $level, boundaries: $boundaries, locations: $locations, assets: $assets) {
                        latitude
                        longitude
                        customers_count
                        customers_ids
                        locations_ids
                        boundingBox {
                            southLatitude
                            northLatitude
                            westLongitude
                            eastLongitude
                        }
                    }
                }
                GRAPHQL,
                $params + [
                    'level' => 1,
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
            'level'      => 2,
            'boundaries' => [
                (new Geohash())->encode(new Coordinate([0, 0]))->getGeohash(),
                (new Geohash())->encode(new Coordinate([10, 10]))->getGeohash(),
            ],
        ];
        $factory = static function (TestCase $test, Organization $organization): void {
            // Customers
            $customerA = Customer::factory()->create([
                'id' => 'ad16444a-46a4-3036-b893-7636e2e6209b',
            ]);
            $customerB = Customer::factory()->create([
                'id' => 'bb699764-e10b-4e09-9fea-dd7a62238dd5',
            ]);

            // Resellers
            $resellerA = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $resellerB = Reseller::factory()->create();

            $resellerA->customers()->attach($customerA);
            $resellerB->customers()->attach($customerB);

            $code    = 0;
            $city    = City::factory()->create([
                'id' => 'c6c90bff-b032-361a-b455-a61e2f3ca288',
            ]);
            $country = Country::factory()->create([
                'id'   => 'c6c90bff-b032-361a-b455-a61e2f3ca289',
                'code' => $code++,
            ]);

            // Inside
            $locationA = Location::factory()->create([
                'id'         => '4d9133ff-482b-4605-870f-9ee88c2062ae',
                'geohash'    => 'u72',
                'latitude'   => 1.00,
                'longitude'  => 1.00,
                'country_id' => Country::factory()->create(['code' => $code++]),
                'city_id'    => $city->getKey(),
            ]);

            $locationA->resellers()->attach($resellerA);
            $locationA->customers()->attach($customerA);

            Asset::factory()->create([
                'location_id' => $locationA,
                'reseller_id' => $resellerA,
                'customer_id' => $customerA,
            ]);

            $locationB = Location::factory()->create([
                'id'         => '6aa4fc05-c3f2-4ad5-a9de-e867772a7335',
                'geohash'    => 'u73',
                'latitude'   => 1.10,
                'longitude'  => 1.10,
                'country_id' => $country->getKey(),
                'city_id'    => City::factory(),
            ]);

            $locationB->customers()->attach($customerA);

            Asset::factory()->create([
                'location_id' => $locationB,
                'customer_id' => $customerA,
            ]);

            $locationC = Location::factory()->create([
                'id'         => '8d8a056f-b224-4d4f-90af-7e0eced13217',
                'geohash'    => 'ue2',
                'latitude'   => 1.25,
                'longitude'  => 1.25,
                'country_id' => Country::factory()->create(['code' => $code++]),
                'city_id'    => City::factory(),
            ]);

            $locationC->customers()->attach($customerB);

            Asset::factory()->create([
                'location_id' => $locationC,
                'customer_id' => $customerB,
            ]);

            $locationD = Location::factory()->create([
                'id'         => '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
                'geohash'    => 'ug2',
                'latitude'   => 1.5,
                'longitude'  => 1.5,
                'country_id' => Country::factory()->create(['code' => $code++]),
                'city_id'    => City::factory(),
            ]);

            $locationD->resellers()->attach($resellerA);
            $locationD->customers()->attach($customerA);
            $locationD->customers()->attach($customerB);

            Asset::factory()->create([
                'location_id' => $locationD,
                'reseller_id' => $resellerB,
                'customer_id' => $customerB,
            ]);

            // No coordinates
            $locationE = Location::factory()->create([
                'latitude'   => null,
                'longitude'  => null,
                'geohash'    => null,
                'country_id' => Country::factory()->create(['code' => $code++]),
                'city_id'    => City::factory(),
            ]);

            $locationE->resellers()->attach($resellerA);
            $locationE->customers()->attach($customerA);

            // Outside
            $locationF = Location::factory()->create([
                'latitude'   => -1.00,
                'longitude'  => 1.00,
                'geohash'    => 'u72',
                'country_id' => Country::factory()->create(['code' => $code++]),
                'city_id'    => City::factory(),
            ]);

            $locationF->resellers()->attach($resellerA);
            $locationF->customers()->attach($customerA);

            // Empty
            Location::factory()->create([
                'latitude'   => 2,
                'longitude'  => 2,
                'geohash'    => 'u72',
                'country_id' => Country::factory()->create(['code' => $code++]),
                'city_id'    => City::factory(),
            ]);
        };

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('map'),
                new OrganizationUserDataProvider('map', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok'              => [
                        new GraphQLSuccess('map', self::class, [
                            [
                                'latitude'        => 1.05,
                                'longitude'       => 1.05,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '4d9133ff-482b-4605-870f-9ee88c2062ae',
                                    '6aa4fc05-c3f2-4ad5-a9de-e867772a7335',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 11.25,
                                    'eastLongitude' => 22.5,
                                ],
                            ],
                            [
                                'latitude'        => 1.25,
                                'longitude'       => 1.25,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_ids'   => [
                                    '8d8a056f-b224-4d4f-90af-7e0eced13217',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 22.5,
                                    'eastLongitude' => 33.75,
                                ],
                            ],
                            [
                                'latitude'        => 1.5,
                                'longitude'       => 1.5,
                                'customers_count' => 2,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_ids'   => [
                                    '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 33.75,
                                    'eastLongitude' => 45,
                                ],
                            ],
                        ]),
                        $factory,
                        $params,
                    ],
                    'filter_city'     => [
                        new GraphQLSuccess('map', self::class, [
                            [
                                'latitude'        => 1,
                                'longitude'       => 1,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '4d9133ff-482b-4605-870f-9ee88c2062ae',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 11.25,
                                    'eastLongitude' => 22.5,
                                ],
                            ],
                        ]),
                        $factory,
                        [
                            'level'     => 2,
                            'locations' => [
                                'allOf' => [
                                    [
                                        'city_id' => [
                                            'equal' => 'c6c90bff-b032-361a-b455-a61e2f3ca288',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'filter_country'  => [
                        new GraphQLSuccess('map', self::class, [
                            [
                                'latitude'        => 1.1,
                                'longitude'       => 1.1,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '6aa4fc05-c3f2-4ad5-a9de-e867772a7335',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 11.25,
                                    'eastLongitude' => 22.5,
                                ],
                            ],
                        ]),
                        $factory,
                        [
                            'level'     => 2,
                            'locations' => [
                                'allOf' => [
                                    [
                                        'country_id' => [
                                            'equal' => 'c6c90bff-b032-361a-b455-a61e2f3ca289',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'filter_customer' => [
                        new GraphQLSuccess('map', self::class, [
                            [
                                'latitude'        => 1.25,
                                'longitude'       => 1.25,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_ids'   => [
                                    '8d8a056f-b224-4d4f-90af-7e0eced13217',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 22.5,
                                    'eastLongitude' => 33.75,
                                ],
                            ],
                            [
                                'latitude'        => 1.5,
                                'longitude'       => 1.5,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_ids'   => [
                                    '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 33.75,
                                    'eastLongitude' => 45,
                                ],
                            ],
                        ]),
                        $factory,
                        array_merge($params, [
                            'assets' => [
                                'customer_id' => [
                                    'equal' => 'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                            ],
                        ]),
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
                                'latitude'        => 1,
                                'longitude'       => 1,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '4d9133ff-482b-4605-870f-9ee88c2062ae',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 11.25,
                                    'eastLongitude' => 22.5,
                                ],
                            ],
                            [
                                'latitude'        => 1.5,
                                'longitude'       => 1.5,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
                                ],
                                'boundingBox'     => [
                                    'southLatitude' => 61.875,
                                    'northLatitude' => 67.5,
                                    'westLongitude' => 33.75,
                                    'eastLongitude' => 45,
                                ],
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
