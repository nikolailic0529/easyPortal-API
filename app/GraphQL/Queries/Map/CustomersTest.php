<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Map;

use App\Models\Organization;
use App\Models\User;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use League\Geotools\Coordinate\Coordinate;
use League\Geotools\Geohash\Geohash;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgResellerDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function array_merge;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Map\Customers
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class CustomersTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                               $orgFactory
     * @param UserFactory                                       $userFactory
     * @param callable(static, ?Organization, ?User): void|null $factory
     * @param array<mixed>                                      $params
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        callable $factory = null,
        array $params = [],
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($factory) {
            $factory($this, $org, $user);
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
                        customers {
                            latitude
                            longitude
                            objects_count
                            objects_ids
                            locations_count
                            locations_ids
                            boundingBox {
                                southLatitude
                                northLatitude
                                westLongitude
                                eastLongitude
                            }
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
        $params = [
            'level'      => 2,
            'boundaries' => [
                (new Geohash())->encode(new Coordinate([0, 0]))->getGeohash(),
                (new Geohash())->encode(new Coordinate([10, 10]))->getGeohash(),
            ],
        ];

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('map', '0d3b991b-24ac-42b3-906b-1957ddafe01b'),
                new OrgUserDataProvider('map', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok'              => [
                        new GraphQLSuccess('map', new JsonFragment('customers', [
                            [
                                'latitude'        => 1.05,
                                'longitude'       => 1.05,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_count' => 2,
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
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_count' => 1,
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
                                'objects_count'   => 2,
                                'objects_ids'     => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_count' => 1,
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
                        ])),
                        new MapDataFactory(),
                        $params,
                    ],
                    'filter_city'     => [
                        new GraphQLSuccess('map', new JsonFragment('customers', [
                            [
                                'latitude'        => 1,
                                'longitude'       => 1,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_count' => 1,
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
                        ])),
                        new MapDataFactory(),
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
                        new GraphQLSuccess('map', new JsonFragment('customers', [
                            [
                                'latitude'        => 1.1,
                                'longitude'       => 1.1,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_count' => 1,
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
                        ])),
                        new MapDataFactory(),
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
                        new GraphQLSuccess('map', new JsonFragment('customers', [
                            [
                                'latitude'        => 1.25,
                                'longitude'       => 1.25,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_count' => 1,
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
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    'bb699764-e10b-4e09-9fea-dd7a62238dd5',
                                ],
                                'locations_count' => 1,
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
                        ])),
                        new MapDataFactory(),
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
                new AuthOrgResellerDataProvider('map', '8d89440a-2615-33ad-b83a-dc94c74ad7bc'),
                new OrgUserDataProvider('map', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('map', new JsonFragment('customers', [
                            [
                                'latitude'        => 1.05,
                                'longitude'       => 1.05,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_count' => 2,
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
                                'latitude'        => 1.5,
                                'longitude'       => 1.5,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    'ad16444a-46a4-3036-b893-7636e2e6209b',
                                ],
                                'locations_count' => 1,
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
                        ])),
                        new MapDataFactory(),
                        $params,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
