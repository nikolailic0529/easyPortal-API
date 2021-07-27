<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
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
 * @coversDefaultClass \App\GraphQL\Queries\AssetsAggregate
 */
class AssetsAggregateTest extends TestCase {
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
                query ($where: SearchByConditionAssetsQuery) {
                    assetsAggregate(where: $where) {
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
                'type_id' => [
                    'eq' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                ],
            ],
        ];
        $factory = static function (TestCase $test, Organization $organization): void {
            // Reseller
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            // Type
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
                ->hasCoverages(1, [
                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'name' => 'name2',
                    'key'  => 'key2',
                ])
                ->create([
                    'type_id'     => $type,
                    'reseller_id' => $reseller,
                ]);
            Asset::factory()->create([
                'type_id'     => $type,
                'reseller_id' => $reseller,
            ]);
            Asset::factory()->create([
                'type_id'     => $type2,
                'reseller_id' => $reseller,
            ]);
        };

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('assetsAggregate'),
                new OrganizationUserDataProvider('assetsAggregate', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('assetsAggregate', AssetsAggregate::class, [
                            'count'     => 2,
                            'types'     => [
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
                new OrganizationDataProvider('assetsAggregate'),
                new UserDataProvider('assetsAggregate', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('assetsAggregate', AssetsAggregate::class, [
                            'count'     => 2,
                            'types'     => [
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
