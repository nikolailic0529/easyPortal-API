<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\Models\Asset;
use App\Models\Data\Type;
use App\Models\Organization;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Database\QueryLog\WithQueryLog;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Assets\AssetsAggregated
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class AssetsAggregatedTest extends TestCase {
    use WithQueryLog;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param array<mixed>        $params
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $factory = null,
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
                query ($where: SearchByConditionAssetsQuery) {
                    assetsAggregated(where: $where) {
                        count
                        types {
                            count
                            type_id
                            type {
                                id
                                key
                                name
                            }
                        }
                        coverages {
                            count
                            coverage_id
                            coverage {
                                id
                                key
                                name
                            }
                        }
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
                'anyOf' => [
                    [
                        'type_id' => [
                            'equal' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        ],
                    ],
                    [
                        'type_id' => [
                            'isNull' => 'yes',
                        ],
                    ],
                ],
            ],
        ];
        $factory = static function (TestCase $test, ?Organization $org): void {
            // Types
            $type  = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name1',
                'key'  => 'key1',
            ]);
            $type2 = Type::factory()->create([
                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
            ]);

            // Assets
            Asset::factory()
                ->ownedBy($org)
                ->hasCoverages(1, [
                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'name' => 'name2',
                    'key'  => 'key2',
                ])
                ->create([
                    'type_id' => $type,
                ]);
            Asset::factory()->ownedBy($org)->create([
                'type_id' => $type,
            ]);
            Asset::factory()->ownedBy($org)->create([
                'type_id' => $type2,
            ]);
            Asset::factory()->ownedBy($org)->create([
                'type_id' => null,
            ]);
        };

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('assetsAggregated'),
                new OrgUserDataProvider('assetsAggregated', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('assetsAggregated', [
                            'count'     => 3,
                            'types'     => [
                                [
                                    'count'   => 1,
                                    'type_id' => null,
                                    'type'    => null,
                                ],
                                [
                                    'count'   => 2,
                                    'type_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    'type'    => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                        'name' => 'name1',
                                        'key'  => 'key1',
                                    ],
                                ],
                            ],
                            'coverages' => [
                                [
                                    'count'       => 1,
                                    'coverage_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                    'coverage'    => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                        'name' => 'name2',
                                        'key'  => 'key2',
                                    ],
                                ],
                            ],
                        ]),
                        $factory,
                        $params,
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('assetsAggregated'),
                new OrgUserDataProvider('assetsAggregated', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('assetsAggregated', [
                            'count'     => 3,
                            'types'     => [
                                [
                                    'count'   => 1,
                                    'type_id' => null,
                                    'type'    => null,
                                ],
                                [
                                    'count'   => 2,
                                    'type_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    'type'    => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                        'name' => 'name1',
                                        'key'  => 'key1',
                                    ],
                                ],
                            ],
                            'coverages' => [
                                [
                                    'count'       => 1,
                                    'coverage_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                    'coverage'    => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                        'name' => 'name2',
                                        'key'  => 'key2',
                                    ],
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
