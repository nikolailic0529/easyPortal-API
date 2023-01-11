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
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
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
 * @covers \App\GraphQL\Queries\Map\Assets
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class AssetsTest extends TestCase {
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
                        assets {
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
                new OrgRootDataProvider('map'),
                new OrgUserDataProvider('map', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok'              => [
                        new GraphQLSuccess('map', new JsonFragment('assets', [
                            [
                                'latitude'        => 1.05,
                                'longitude'       => 1.05,
                                'objects_count'   => 2,
                                'objects_ids'     => [
                                    '2e65b276-b7fe-4c18-8ace-c25944533ba9',
                                    '3a6f6d2e-591d-4a0a-8eb4-b56d1b2617ab',
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
                                    '8f53d628-838c-4869-b3fe-70b9002952fc',
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
                                    'ef5cda9e-a818-4fcf-bf86-e1e41ceb8ed8',
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
                        new GraphQLSuccess('map', new JsonFragment('assets', [
                            [
                                'latitude'        => 1,
                                'longitude'       => 1,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    '3a6f6d2e-591d-4a0a-8eb4-b56d1b2617ab',
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
                        new GraphQLSuccess('map', new JsonFragment('assets', [
                            [
                                'latitude'        => 1.1,
                                'longitude'       => 1.1,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    '2e65b276-b7fe-4c18-8ace-c25944533ba9',
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
                        new GraphQLSuccess('map', new JsonFragment('assets', [
                            [
                                'latitude'        => 1.25,
                                'longitude'       => 1.25,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    '8f53d628-838c-4869-b3fe-70b9002952fc',
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
                                    'ef5cda9e-a818-4fcf-bf86-e1e41ceb8ed8',
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
                new AuthOrgDataProvider('map'),
                new OrgUserDataProvider('map', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('map', new JsonFragment('assets', [
                            [
                                'latitude'        => 1,
                                'longitude'       => 1,
                                'objects_count'   => 1,
                                'objects_ids'     => [
                                    '3a6f6d2e-591d-4a0a-8eb4-b56d1b2617ab',
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
                        $params,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
