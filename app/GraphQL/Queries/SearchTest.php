<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Status;
use App\Models\Type;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;
use Tests\WithSearch;

use function array_values;
use function count;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Search
 */
class SearchTest extends TestCase {
    use WithSearch;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $factory = null,
        string $search = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($settings) {
            $this->setSettings($settings);
        }

        if ($factory) {
            $this->makeSearchable($factory($this, $organization, $user));
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query search($search: SearchString!) {
                    search(search: $search) {
                        __typename
                        ... on Asset {
                            id
                        }
                        ... on Customer {
                            id
                        }
                        ... on Document {
                            id
                        }
                    }
                    searchAggregated(search: $search) {
                        count
                    }
                }
                GRAPHQL,
                [
                    'search' => $search ?: '*',
                ],
            )
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string,mixed>
     */
    public function dataProviderInvoke(): array {
        $settings                = [
            'ep.document_statuses_hidden' => [
                'fb377814-592d-492c-aa05-e9e01afd4a11',
            ],
            'ep.contract_types'           => [
                'a4cd3a25-5b9f-41c5-aa93-23c770086d6c',
            ],
            'ep.quote_types'              => [
                '453a47d0-6607-4cf7-8d0a-bd57a962658a',
            ],
        ];
        $factory                 = static function (TestCase $test, Organization $organization): Collection {
            $status          = Status::factory()->create([
                'id' => 'fb377814-592d-492c-aa05-e9e01afd4a11',
            ]);
            $reseller        = Reseller::factory()->create([
                'id' => $organization,
            ]);
            $customerHidden  = Customer::factory()->create([
                'id'   => 'ae85870f-1593-4eb5-ae08-ee00f0688d04',
                'name' => 'Customer ABC',
            ]);
            $customerVisible = Customer::factory()->create([
                'id'   => '0dea40cd-08a6-433b-b984-b8be88dde767',
                'name' => 'Customer ABC',
            ]);

            $customerVisible->resellers()->attach($reseller);

            $assetHidden  = Asset::factory()->create([
                'id'            => 'b0e5d0dc-cf6b-4a9f-b6dd-3aaee47e3f9d',
                'serial_number' => 'Asset ABC',
            ]);
            $assetVisible = Asset::factory()->create([
                'id'            => 'cb8943e3-bf2f-4e8f-80f4-d3d0f5afc5c9',
                'reseller_id'   => $reseller,
                'serial_number' => 'Asset ABC',
            ]);

            $contractType    = Type::factory()->create([
                'id' => 'a4cd3a25-5b9f-41c5-aa93-23c770086d6c',
            ]);
            $contractHidden  = Document::factory()->create([
                'type_id'     => $contractType,
                'reseller_id' => $reseller,
                'number'      => 'Hidden Contract ABC',
                'statuses'    => [$status],
            ]);
            $contractIgnored = Document::factory()->create([
                'id'      => '474bbaf1-a30f-4dfd-a81e-10ebabe6ccb5',
                'type_id' => $contractType,
                'number'  => 'Ignored Contract ABC',
            ]);
            $contractVisible = Document::factory()->create([
                'id'          => '9d9bb184-cf20-437e-a6f6-2d5268f8814b',
                'type_id'     => $contractType,
                'reseller_id' => $reseller,
                'number'      => 'Visible Contract ABC',
            ]);

            $quoteType    = Type::factory()->create([
                'id' => '453a47d0-6607-4cf7-8d0a-bd57a962658a',
            ]);
            $quoteHidden  = Document::factory()->create([
                'type_id'     => $quoteType,
                'reseller_id' => $reseller,
                'number'      => 'Hidden Quote ABC',
                'statuses'    => [$status],
            ]);
            $quoteIgnored = Document::factory()->create([
                'id'      => '2d5a2cb9-b2b8-4a25-8f60-350af319fc0d',
                'type_id' => $quoteType,
                'number'  => 'Ignored Quote ABC',
            ]);
            $quoteVisible = Document::factory()->create([
                'id'          => 'a3e3d637-dc22-4283-a170-af950e1f2996',
                'type_id'     => $quoteType,
                'reseller_id' => $reseller,
                'number'      => 'Visible Quote ABC',
            ]);

            $document = Document::factory()->create([
                'id'          => 'bee68abc-9b04-42c2-a78c-c35cf57aeb14',
                'reseller_id' => $reseller,
                'number'      => 'Quote OR Contract OR ABC',
            ]);

            return new Collection([
                $customerHidden,
                $customerVisible,
                $assetHidden,
                $assetVisible,
                $contractHidden,
                $contractIgnored,
                $contractVisible,
                $quoteHidden,
                $quoteIgnored,
                $quoteVisible,
                $document,
            ]);
        };
        $objects  = [
            'Customer' => [
                '__typename' => 'Customer',
                'id'         => '0dea40cd-08a6-433b-b984-b8be88dde767',
            ],
            'Asset'    => [
                '__typename' => 'Asset',
                'id'         => 'cb8943e3-bf2f-4e8f-80f4-d3d0f5afc5c9',
            ],
            'Contract' => [
                '__typename' => 'Document',
                'id'         => '9d9bb184-cf20-437e-a6f6-2d5268f8814b',
            ],
            'Quote'    => [
                '__typename' => 'Document',
                'id'         => 'a3e3d637-dc22-4283-a170-af950e1f2996',
            ],
        ];

        return (new MergeDataProvider([
            'root'           => new CompositeDataProvider(
                new AuthOrgRootDataProvider('search'),
                new OrgUserDataProvider('search', [
                    'customers-view', 'assets-view', 'quotes-view', 'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('search', self::class),
                        $settings,
                        static function (TestCase $test, Organization $organization): Collection {
                            return new Collection([
                                Customer::factory()->create(),
                                Asset::factory()->create(),
                                Document::factory()->create([
                                    'type_id' => Type::factory()->create([
                                        'id' => 'a4cd3a25-5b9f-41c5-aa93-23c770086d6c',
                                    ]),
                                ]),
                            ]);
                        },
                        null,
                    ],
                ]),
            ),
            'organization'   => new CompositeDataProvider(
                new AuthOrgDataProvider('search'),
                new OrgUserDataProvider('search', [
                    'customers-view', 'assets-view', 'quotes-view', 'contracts-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, array_values($objects), [
                            'count' => count($objects),
                        ]),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Asset']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Asset A',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, [$objects['Customer']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Customer A',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, [$objects['Contract']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Contract A',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Quote']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Quote A',
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('search'),
                new OrgUserDataProvider('search', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, array_values($objects), [
                            'count' => count($objects),
                        ]),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Asset']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, [$objects['Customer']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, [$objects['Contract']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Quote']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Quote',
                    ],
                ]),
            ),
            'assets-view'    => new CompositeDataProvider(
                new AuthOrgDataProvider('search'),
                new OrgUserDataProvider('search', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, [$objects['Asset']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Asset']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Quote',
                    ],
                ]),
            ),
            'quotes-view'    => new CompositeDataProvider(
                new AuthOrgDataProvider('search'),
                new OrgUserDataProvider('search', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, [$objects['Quote']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Quote']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Quote',
                    ],
                ]),
            ),
            'contracts-view' => new CompositeDataProvider(
                new AuthOrgDataProvider('search'),
                new OrgUserDataProvider('search', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, [$objects['Contract']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, [$objects['Contract']], [
                            'count' => 1,
                        ]),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, [], [
                            'count' => 0,
                        ]),
                        $settings,
                        $factory,
                        'Quote',
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
