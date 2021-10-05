<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\City;
use App\Models\Country;
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
    // <editor-fold desc="Tests">
    // =========================================================================
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
                        customers_ids
                        locations_ids
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

    /**
     * @covers ::getSearchByWhere
     */
    public function testGetSearchByWhere(): void {
        $field    = 'customers';
        $fields   = ['allOf', 'anyOf', 'not'];
        $where    = [
            'allOf' => [
                [
                    'latitude' => [
                        'between' => [
                            'min' => 48.153653203996,
                            'max' => 49.953365844075,
                        ],
                    ],
                ],
                [
                    'customers' => [
                        'where' => [
                            'allOf' => [
                                [
                                    'name' => [
                                        'in' => [
                                            'abc',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'not' => [
                        [
                            'customers' => [
                                'where' => [
                                    'allOf' => [
                                        [
                                            'name' => [
                                                'in' => [
                                                    'abc',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'customers' => [
                        'where' => [
                            'allOf' => [
                                [
                                    'name' => [
                                        'in' => [
                                            'abc',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $resolver = new class() extends Map {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getSearchByWhere(string $field, array $fields, array $where): array {
                return parent::getSearchByWhere($field, $fields, $where);
            }
        };
        $actual   = $resolver->getSearchByWhere($field, $fields, $where);
        $expected = [
            'allOf' => [
                [
                    'allOf' => [
                        [
                            'name' => [
                                'in' => [
                                    'abc',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'not' => [
                        [
                            'allOf' => [
                                [
                                    'name' => [
                                        'in' => [
                                            'abc',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'allOf' => [
                        [
                            'name' => [
                                'in' => [
                                    'abc',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertEquals($expected, $actual);
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
                'id' => 'bb699764-e10b-4e09-9fea-dd7a62238dd5',
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
                'id'              => '4d9133ff-482b-4605-870f-9ee88c2062ae',
                'latitude'        => 1.00,
                'longitude'       => 1.00,
                'country_id'      => Country::factory()->create(['code' => $code++]),
                'city_id'         => $city->getKey(),
                'customers_count' => 1,
            ]);

            $locationA->resellers()->attach($resellerA, [
                'customers_count' => 1,
            ]);
            $locationA->customers()->attach($customerA);

            $locationB = Location::factory()->create([
                'id'              => '6aa4fc05-c3f2-4ad5-a9de-e867772a7335',
                'latitude'        => 1.10,
                'longitude'       => 1.10,
                'country_id'      => $country->getKey(),
                'city_id'         => City::factory(),
                'customers_count' => 1,
            ]);

            $locationB->customers()->attach($customerA);

            $locationC = Location::factory()->create([
                'id'              => '8d8a056f-b224-4d4f-90af-7e0eced13217',
                'latitude'        => 1.25,
                'longitude'       => 1.25,
                'country_id'      => Country::factory()->create(['code' => $code++]),
                'city_id'         => City::factory(),
                'customers_count' => 1,
            ]);

            $locationC->customers()->attach($customerB);

            $locationD = Location::factory()->create([
                'id'              => '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
                'latitude'        => 1.5,
                'longitude'       => 1.5,
                'country_id'      => Country::factory()->create(['code' => $code++]),
                'city_id'         => City::factory(),
                'customers_count' => 2,
            ]);

            $locationD->resellers()->attach($resellerA, [
                'customers_count' => 1,
            ]);
            $locationD->customers()->attach($customerA);
            $locationD->customers()->attach($customerB);

            // Empty
            Location::factory()->create([
                'latitude'   => 2,
                'longitude'  => 2,
                'country_id' => Country::factory()->create(['code' => $code++]),
                'city_id'    => City::factory(),
            ]);

            // Outside
            Location::factory()->create([
                'latitude'   => -1.00,
                'longitude'  => 1.00,
                'country_id' => Country::factory()->create(['code' => $code++]),
                'city_id'    => City::factory(),
            ]);
            Location::factory()->create([
                'latitude'   => 1.00,
                'longitude'  => -1.00,
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
                                'latitude_avg'    => 1.05,
                                'latitude_min'    => 1,
                                'latitude_max'    => 1.1,
                                'longitude_avg'   => 1.05,
                                'longitude_min'   => 1,
                                'longitude_max'   => 1.1,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '4d9133ff-482b-4605-870f-9ee88c2062ae',
                                    '6aa4fc05-c3f2-4ad5-a9de-e867772a7335',
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
                                'customers_ids'   => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_ids'   => [
                                    '8d8a056f-b224-4d4f-90af-7e0eced13217',
                                ],
                            ],
                            [
                                'latitude_avg'    => 1.5,
                                'latitude_min'    => 1.5,
                                'latitude_max'    => 1.5,
                                'longitude_avg'   => 1.5,
                                'longitude_min'   => 1.5,
                                'longitude_max'   => 1.5,
                                'customers_count' => 2,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_ids'   => [
                                    '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
                                ],
                            ],
                        ]),
                        $factory,
                        $params,
                    ],
                    'filter_city'     => [
                        new GraphQLSuccess('map', self::class, [
                            [
                                'latitude_avg'    => 1,
                                'latitude_min'    => 1,
                                'latitude_max'    => 1,
                                'longitude_avg'   => 1,
                                'longitude_min'   => 1,
                                'longitude_max'   => 1,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '4d9133ff-482b-4605-870f-9ee88c2062ae',
                                ],
                            ],
                        ]),
                        $factory,
                        [
                            'where' => [
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
                                'latitude_avg'    => 1.1,
                                'latitude_min'    => 1.1,
                                'latitude_max'    => 1.1,
                                'longitude_avg'   => 1.1,
                                'longitude_min'   => 1.1,
                                'longitude_max'   => 1.1,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '6aa4fc05-c3f2-4ad5-a9de-e867772a7335',
                                ],
                            ],
                        ]),
                        $factory,
                        [
                            'where' => [
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
                                'latitude_avg'    => 1.25,
                                'latitude_min'    => 1.25,
                                'latitude_max'    => 1.25,
                                'longitude_avg'   => 1.25,
                                'longitude_min'   => 1.25,
                                'longitude_max'   => 1.25,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_ids'   => [
                                    '8d8a056f-b224-4d4f-90af-7e0eced13217',
                                ],
                            ],
                            [
                                'latitude_avg'    => 1.5,
                                'latitude_min'    => 1.5,
                                'latitude_max'    => 1.5,
                                'longitude_avg'   => 1.5,
                                'longitude_min'   => 1.5,
                                'longitude_max'   => 1.5,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_ids'   => [
                                    '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
                                ],
                            ],
                        ]),
                        $factory,
                        [
                            'diff'  => 0.25,
                            'where' => [
                                'customers' => [
                                    'where' => [
                                        'id' => [
                                            'equal' => 'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                        ],
                                    ],
                                ],
                            ],
                        ],
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
                                'latitude_avg'    => 1,
                                'latitude_min'    => 1,
                                'latitude_max'    => 1,
                                'longitude_avg'   => 1,
                                'longitude_min'   => 1,
                                'longitude_max'   => 1,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '4d9133ff-482b-4605-870f-9ee88c2062ae',
                                ],
                            ],
                            [
                                'latitude_avg'    => 1.5,
                                'latitude_min'    => 1.5,
                                'latitude_max'    => 1.5,
                                'longitude_avg'   => 1.5,
                                'longitude_min'   => 1.5,
                                'longitude_max'   => 1.5,
                                'customers_count' => 1,
                                'customers_ids'   => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_ids'   => [
                                    '6162c51f-1c24-4e03-a3e7-b26975c7bac7',
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
