<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Organization;
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
                /** @lang GraphQL */ <<<'GRAPHQL'
                query ($where: SearchByConditionMapQuery, $diff: Float!) {
                    map (where: $where, diff: $diff) {
                        latitude
                        longitude
                        customers
                        assets
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
            // Inside
            $a = Location::factory()->create([
                'latitude'  => 1.00,
                'longitude' => 1.00,
            ]);
            $b = Location::factory()->create([
                'latitude'  => 1.10,
                'longitude' => 1.10,
                'object_id' => Customer::factory()->create(),
            ]);
            Location::factory()->create([
                'latitude'  => 1.25,
                'longitude' => 1.25,
                'object_id' => Customer::factory()->create(),
            ]);

            Asset::factory()->create([
                'location_id' => $a,
            ]);
            Asset::factory()->create([
                'location_id' => $a,
            ]);
            Asset::factory()->create([
                'location_id' => $b,
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
        $result  = [
            [
                'latitude'  => 1.033333333333,
                'longitude' => 1.033333333333,
                'customers' => 1,
                'assets'    => 3,
            ],
            [
                'latitude'  => 1.25,
                'longitude' => 1.25,
                'customers' => 1,
                'assets'    => 0,
            ],
        ];

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('map'),
                new OrganizationUserDataProvider('map', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('map', self::class, $result),
                        $factory,
                        $params,
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('map'),
                new UserDataProvider('map', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('map', self::class, $result),
                        $factory,
                        $params,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
