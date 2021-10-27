<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\Models\Customer;
use App\Models\CustomerLocation;
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
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class CustomersTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     *
     * @param array<mixed> $settings
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $this->setSettings($settings);

        if ($customerFactory) {
            $customerFactory($this, $organization, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
            customers {
                id
                name
                assets_count
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
            }
        }')->assertThat($expected);
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
                new RootOrganizationDataProvider('customers'),
                new OrganizationUserDataProvider('customers', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('customers', null),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('customers'),
                new OrganizationUserDataProvider('customers', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated(
                            'customers',
                            self::class,
                            [
                                [
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 0,
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
                                    ],
                                    'changed_at'      => '2021-10-19T10:15:00+00:00',
                                    'synced_at'       => '2021-10-19T10:25:00+00:00',
                                ],
                            ],
                            [
                                'count' => 1,
                            ],
                        ),
                        [
                            'ep.headquarter_type' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        ],
                        static function (TestCase $test, Organization $organization): void {
                            /** @var \App\Models\Reseller $reseller */
                            $reseller = Reseller::factory()->create([
                                'id' => $organization,
                            ]);

                            /** @var \App\Models\Location $location */
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

                            /** @var \App\Models\Customer $customer */
                            $customer = Customer::factory()
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
                                ->hasKpi(1, [
                                    'object_type'                         => (new Customer())->getMorphClass(),
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
                                ])
                                ->create([
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 0,
                                    'contacts_count'  => 1,
                                    'locations_count' => 1,
                                    'changed_at'      => '2021-10-19 10:15:00',
                                    'synced_at'       => '2021-10-19 10:25:00',
                                ]);

                            $customer->resellers()->attach($reseller);

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
    // </editor-fold>
}