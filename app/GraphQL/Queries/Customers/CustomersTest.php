<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Location;
use App\Models\Kpi;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgResellerDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @coversNothing
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CustomersTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                              $orgFactory
     * @param UserFactory                                      $userFactory
     * @param SettingsFactory                                  $settingsFactory
     * @param Closure(static, ?Organization, ?User): void|null $customerFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        if ($customerFactory) {
            $customerFactory($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
            customers {
                id
                name
                assets_count
                quotes_count
                contracts_count
                contacts_count
                locations_count
                locations {
                    location_id
                    location {
                        id
                        state
                        postcode
                        line_one
                        line_two
                        latitude
                        longitude
                    }
                    types {
                      id
                      name
                    }
                }
                headquarter {
                    location_id
                    location {
                        id
                        state
                        postcode
                        line_one
                        line_two
                        latitude
                        longitude
                    }
                    types {
                        id
                        name
                    }
                }
                contacts {
                    name
                    email
                    phone_valid
                }
                statuses {
                    id
                    key
                    name
                }
                kpi {
                    assets_total
                    assets_active
                    assets_active_percent
                    assets_active_on_contract
                    assets_active_on_warranty
                    assets_active_exposed
                    customers_active
                    customers_active_new
                    contracts_active
                    contracts_active_amount
                    contracts_active_new
                    contracts_expiring
                    contracts_expired
                    quotes_active
                    quotes_active_amount
                    quotes_active_new
                    quotes_expiring
                    quotes_expired
                    quotes_ordered
                    quotes_accepted
                    quotes_requested
                    quotes_received
                    quotes_rejected
                    quotes_awaiting
                    service_revenue_total_amount
                    service_revenue_total_amount_change
                }
                changed_at
                synced_at
            }
            customersAggregated {
                count
                groups(groupBy: {name: asc}) {
                    key
                    count
                }
                groupsAggregated(groupBy: {name: asc}) {
                    count
                }
            }
        }')->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderOrgProperty
     *
     * @param OrganizationFactory                              $orgFactory
     * @param UserFactory                                      $userFactory
     * @param SettingsFactory                                  $settingsFactory
     * @param Closure(static, ?Organization, ?User): void|null $customerFactory
     * @param array<string, mixed>                             $variables
     */
    public function testOrgProperty(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $customerFactory = null,
        array $variables = [],
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        if ($customerFactory) {
            $customerFactory($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query test($where: SearchByConditionCompaniesQuery, $order: [SortByClauseCompaniesSort!]) {
                    customers(where: $where, order: $order) {
                        assets_count
                    }
                }
                GRAPHQL,
                $variables,
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
        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('customers'),
                new OrgUserDataProvider('customers', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('customers'),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgResellerDataProvider('customers'),
                new OrgUserDataProvider('customers', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated(
                            'customers',
                            [
                                [
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 1,
                                    'quotes_count'    => 1,
                                    'contracts_count' => 1,
                                    'locations_count' => 1,
                                    'locations'       => [
                                        [
                                            'location_id' => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                            'location'    => [
                                                'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                                'state'     => 'state1',
                                                'postcode'  => '19911',
                                                'line_one'  => 'line_one_data',
                                                'line_two'  => 'line_two_data',
                                                'latitude'  => 47.91634204,
                                                'longitude' => -2.26318359,
                                            ],
                                            'types'       => [
                                                [
                                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                                    'name' => 'headquarter',
                                                ],
                                            ],
                                        ],
                                    ],
                                    'contacts_count'  => 1,
                                    'contacts'        => [
                                        [
                                            'name'        => 'contact1',
                                            'email'       => 'contact1@test.com',
                                            'phone_valid' => false,
                                        ],
                                    ],
                                    'headquarter'     => [
                                        'location_id' => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                        'location'    => [
                                            'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                            'state'     => 'state1',
                                            'postcode'  => '19911',
                                            'line_one'  => 'line_one_data',
                                            'line_two'  => 'line_two_data',
                                            'latitude'  => 47.91634204,
                                            'longitude' => -2.26318359,
                                        ],
                                        'types'       => [
                                            [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                                'name' => 'headquarter',
                                            ],
                                        ],
                                    ],
                                    'statuses'        => [
                                        [
                                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                            'key'  => 'active',
                                            'name' => 'active',
                                        ],
                                    ],
                                    'kpi'             => [
                                        'assets_total'                        => 1_001,
                                        'assets_active'                       => 1_002,
                                        'assets_active_percent'               => 1_003.0,
                                        'assets_active_on_contract'           => 1_004,
                                        'assets_active_on_warranty'           => 1_005,
                                        'assets_active_exposed'               => 1_006,
                                        'customers_active'                    => 1_007,
                                        'customers_active_new'                => 1_008,
                                        'contracts_active'                    => 1_009,
                                        'contracts_active_amount'             => 1_0010.0,
                                        'contracts_active_new'                => 1_0011,
                                        'contracts_expiring'                  => 1_0012,
                                        'contracts_expired'                   => 1_0013,
                                        'quotes_active'                       => 1_0014,
                                        'quotes_active_amount'                => 1_0015.0,
                                        'quotes_active_new'                   => 1_0016,
                                        'quotes_expiring'                     => 1_0017,
                                        'quotes_expired'                      => 1_0018,
                                        'quotes_ordered'                      => 1_0019,
                                        'quotes_accepted'                     => 1_0020,
                                        'quotes_requested'                    => 1_0021,
                                        'quotes_received'                     => 1_0022,
                                        'quotes_rejected'                     => 1_0023,
                                        'quotes_awaiting'                     => 1_0024,
                                        'service_revenue_total_amount'        => 1_0025.0,
                                        'service_revenue_total_amount_change' => 1_0026.0,
                                    ],
                                    'changed_at'      => '2021-10-19T10:15:00+00:00',
                                    'synced_at'       => '2021-10-19T10:25:00+00:00',
                                ],
                            ],
                            [
                                'count'            => 1,
                                'groups'           => [
                                    [
                                        'key'   => 'name aaa',
                                        'count' => 1,
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 1,
                                ],
                            ],
                        ),
                        [
                            'ep.headquarter_type' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): void {
                            $kpi = Kpi::factory()->create([
                                'assets_total'                        => 1,
                                'assets_active'                       => 2,
                                'assets_active_percent'               => 3.0,
                                'assets_active_on_contract'           => 4,
                                'assets_active_on_warranty'           => 5,
                                'assets_active_exposed'               => 6,
                                'customers_active'                    => 7,
                                'customers_active_new'                => 8,
                                'contracts_active'                    => 9,
                                'contracts_active_amount'             => 10.0,
                                'contracts_active_new'                => 11,
                                'contracts_expiring'                  => 12,
                                'contracts_expired'                   => 13,
                                'quotes_active'                       => 14,
                                'quotes_active_amount'                => 15.0,
                                'quotes_active_new'                   => 16,
                                'quotes_expiring'                     => 17,
                                'quotes_expired'                      => 18,
                                'quotes_ordered'                      => 19,
                                'quotes_accepted'                     => 20,
                                'quotes_requested'                    => 21,
                                'quotes_received'                     => 22,
                                'quotes_rejected'                     => 23,
                                'quotes_awaiting'                     => 24,
                                'service_revenue_total_amount'        => 25.0,
                                'service_revenue_total_amount_change' => 26.0,
                            ]);

                            /** @var Reseller $reseller */
                            $reseller = Reseller::factory()->create([
                                'id' => $organization,
                            ]);

                            /** @var \App\Models\Data\Location $location */
                            $location = Location::factory()->create([
                                'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'     => 'state1',
                                'postcode'  => '19911',
                                'line_one'  => 'line_one_data',
                                'line_two'  => 'line_two_data',
                                'latitude'  => '47.91634204',
                                'longitude' => '-2.26318359',
                            ]);

                            $location->resellers()->attach($reseller);

                            /** @var Customer $customer */
                            $customer    = Customer::factory()
                                ->hasContacts(1, [
                                    'name'        => 'contact1',
                                    'email'       => 'contact1@test.com',
                                    'phone_valid' => false,
                                ])
                                ->hasStatuses(1, [
                                    'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                    'name'        => 'active',
                                    'key'         => 'active',
                                    'object_type' => (new Customer())->getMorphClass(),
                                ])
                                ->create([
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'kpi_id'          => $kpi,
                                    'assets_count'    => 0,
                                    'quotes_count'    => 0,
                                    'contracts_count' => 0,
                                    'contacts_count'  => 1,
                                    'locations_count' => 1,
                                    'changed_at'      => '2021-10-19 10:15:00',
                                    'synced_at'       => '2021-10-19 10:25:00',
                                ]);
                            $customerKpi = Kpi::factory()->create([
                                'assets_total'                        => 1_001,
                                'assets_active'                       => 1_002,
                                'assets_active_percent'               => 1_003.0,
                                'assets_active_on_contract'           => 1_004,
                                'assets_active_on_warranty'           => 1_005,
                                'assets_active_exposed'               => 1_006,
                                'customers_active'                    => 1_007,
                                'customers_active_new'                => 1_008,
                                'contracts_active'                    => 1_009,
                                'contracts_active_amount'             => 1_0010.0,
                                'contracts_active_new'                => 1_0011,
                                'contracts_expiring'                  => 1_0012,
                                'contracts_expired'                   => 1_0013,
                                'quotes_active'                       => 1_0014,
                                'quotes_active_amount'                => 1_0015.0,
                                'quotes_active_new'                   => 1_0016,
                                'quotes_expiring'                     => 1_0017,
                                'quotes_expired'                      => 1_0018,
                                'quotes_ordered'                      => 1_0019,
                                'quotes_accepted'                     => 1_0020,
                                'quotes_requested'                    => 1_0021,
                                'quotes_received'                     => 1_0022,
                                'quotes_rejected'                     => 1_0023,
                                'quotes_awaiting'                     => 1_0024,
                                'service_revenue_total_amount'        => 1_0025.0,
                                'service_revenue_total_amount_change' => 1_0026.0,
                            ]);

                            $customer->resellers()->attach($reseller, [
                                'assets_count'    => 1,
                                'quotes_count'    => 1,
                                'contracts_count' => 1,
                                'kpi_id'          => $customerKpi->getKey(),
                            ]);

                            CustomerLocation::factory()
                                ->hasTypes(1, [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    'name' => 'headquarter',
                                ])
                                ->create([
                                    'customer_id' => $customer,
                                    'location_id' => $location,
                                ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderOrgProperty(): array {
        return (new MergeDataProvider([
            'organization' => new CompositeDataProvider(
                new AuthOrgResellerDataProvider('customers'),
                new OrgUserDataProvider('customers', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess(
                            'customers',
                            [
                                [
                                    'assets_count' => 4,
                                ],
                                [
                                    'assets_count' => 3,
                                ],
                                [
                                    'assets_count' => 2,
                                ],
                            ],
                        ),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): void {
                            $reseller = Reseller::factory()->create([
                                'id' => $org,
                            ]);

                            Customer::factory()
                                ->create([
                                    'assets_count' => 0,
                                ])
                                ->resellers()
                                ->attach($reseller, [
                                    'assets_count' => 1,
                                ]);
                            Customer::factory()
                                ->create([
                                    'assets_count' => 0,
                                ])
                                ->resellers()
                                ->attach($reseller, [
                                    'assets_count' => 2,
                                ]);
                            Customer::factory()
                                ->create([
                                    'assets_count' => 0,
                                ])
                                ->resellers()
                                ->attach($reseller, [
                                    'assets_count' => 3,
                                ]);
                            Customer::factory()
                                ->create([
                                    'assets_count' => 0,
                                ])
                                ->resellers()
                                ->attach($reseller, [
                                    'assets_count' => 4,
                                ]);
                        },
                        [
                            'where' => [
                                'assets_count' => [
                                    'greaterThan' => 1,
                                ],
                            ],
                            'order' => [
                                [
                                    'assets_count' => 'desc',
                                ],
                            ],
                        ],
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
