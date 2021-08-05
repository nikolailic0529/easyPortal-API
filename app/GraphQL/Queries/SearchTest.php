<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Type;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;
use Tests\WithScout;

use function array_values;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Search
 */
class SearchTest extends TestCase {
    use WithScout;

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
                query search($search: String!) {
                    search(search: $search) {
                        data {
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
                        paginatorInfo {
                            count
                            currentPage
                            firstItem
                            hasMorePages
                            lastItem
                            lastPage
                            perPage
                            total
                        }
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
        $settings = [
            'ep.contract_types' => [
                'a4cd3a25-5b9f-41c5-aa93-23c770086d6c',
            ],
            'ep.quote_types'    => [
                '453a47d0-6607-4cf7-8d0a-bd57a962658a',
            ],
        ];
        $factory  = static function (TestCase $test, Organization $organization): Collection {
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
                'id'      => '474bbaf1-a30f-4dfd-a81e-10ebabe6ccb5',
                'type_id' => $contractType,
                'number'  => 'Contract ABC',
            ]);
            $contractVisible = Document::factory()->create([
                'id'          => '9d9bb184-cf20-437e-a6f6-2d5268f8814b',
                'type_id'     => $contractType,
                'reseller_id' => $reseller,
                'number'      => 'Contract ABC',
            ]);

            $quoteType    = Type::factory()->create([
                'id' => '453a47d0-6607-4cf7-8d0a-bd57a962658a',
            ]);
            $quoteHidden  = Document::factory()->create([
                'id'      => '2d5a2cb9-b2b8-4a25-8f60-350af319fc0d',
                'type_id' => $quoteType,
                'number'  => 'Quote ABC',
            ]);
            $quoteVisible = Document::factory()->create([
                'id'          => 'a3e3d637-dc22-4283-a170-af950e1f2996',
                'type_id'     => $quoteType,
                'reseller_id' => $reseller,
                'number'      => 'Quote ABC',
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
                $contractVisible,
                $quoteHidden,
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
                new RootOrganizationDataProvider('search'),
                new OrganizationUserDataProvider('search', [
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
                new OrganizationDataProvider('search'),
                new OrganizationUserDataProvider('search', [
                    'customers-view', 'assets-view', 'quotes-view', 'contracts-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, array_values($objects)),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Asset']]),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, [$objects['Customer']]),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, [$objects['Contract']]),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Quote']]),
                        $settings,
                        $factory,
                        'Quote',
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('search'),
                new OrganizationUserDataProvider('search', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, array_values($objects)),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Asset']]),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, [$objects['Customer']]),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, [$objects['Contract']]),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Quote']]),
                        $settings,
                        $factory,
                        'Quote',
                    ],
                ]),
            ),
            'assets-view'    => new CompositeDataProvider(
                new OrganizationDataProvider('search'),
                new OrganizationUserDataProvider('search', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, [$objects['Asset']]),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Asset']]),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, []),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, []),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, []),
                        $settings,
                        $factory,
                        'Quote',
                    ],
                ]),
            ),
            'quotes-view'    => new CompositeDataProvider(
                new OrganizationDataProvider('search'),
                new OrganizationUserDataProvider('search', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, [$objects['Quote']]),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, []),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, []),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, []),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, [$objects['Quote']]),
                        $settings,
                        $factory,
                        'Quote',
                    ],
                ]),
            ),
            'contracts-view' => new CompositeDataProvider(
                new OrganizationDataProvider('search'),
                new OrganizationUserDataProvider('search', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'search all'      => [
                        new GraphQLPaginated('search', self::class, [$objects['Contract']]),
                        $settings,
                        $factory,
                        'ABC',
                    ],
                    'search asset'    => [
                        new GraphQLPaginated('search', self::class, []),
                        $settings,
                        $factory,
                        'Asset',
                    ],
                    'search customer' => [
                        new GraphQLPaginated('search', self::class, []),
                        $settings,
                        $factory,
                        'Customer',
                    ],
                    'search contract' => [
                        new GraphQLPaginated('search', self::class, [$objects['Contract']]),
                        $settings,
                        $factory,
                        'Contract',
                    ],
                    'search quote'    => [
                        new GraphQLPaginated('search', self::class, []),
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
