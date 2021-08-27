<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

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
                data {
                    id
                    name
                    assets_count
                    contacts_count
                    locations_count
                    locations {
                        id
                        state
                        postcode
                        line_one
                        line_two
                        latitude
                        longitude
                    }
                    headquarter {
                        id
                        state
                        postcode
                        line_one
                        line_two
                        latitude
                        longitude
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
                        assets_covered
                        customers_active
                        customers_active_new
                        contracts_active
                        contracts_active_amount
                        contracts_active_new
                        contracts_expiring
                        quotes_active
                        quotes_active_amount
                        quotes_active_new
                        quotes_expiring
                    }
                },
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
                        new GraphQLPaginated('customers', self::class, [
                            [
                                'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name'            => 'name aaa',
                                'assets_count'    => 0,
                                'locations_count' => 1,
                                'locations'       => [
                                    [
                                        'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                        'state'     => 'state1',
                                        'postcode'  => '19911',
                                        'line_one'  => 'line_one_data',
                                        'line_two'  => 'line_two_data',
                                        'latitude'  => 47.91634204,
                                        'longitude' => -2.26318359,
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
                                    'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                    'state'     => 'state1',
                                    'postcode'  => '19911',
                                    'line_one'  => 'line_one_data',
                                    'line_two'  => 'line_two_data',
                                    'latitude'  => 47.91634204,
                                    'longitude' => -2.26318359,
                                ],
                                'statuses'        => [
                                    [
                                        'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                        'key'  => 'active',
                                        'name' => 'active',
                                    ],
                                ],
                                'kpi'             => [
                                    'assets_total'            => 1,
                                    'assets_active'           => 2,
                                    'assets_covered'          => 3.0,
                                    'customers_active'        => 4,
                                    'customers_active_new'    => 5,
                                    'contracts_active'        => 6,
                                    'contracts_active_amount' => 7.0,
                                    'contracts_active_new'    => 8,
                                    'contracts_expiring'      => 9,
                                    'quotes_active'           => 10,
                                    'quotes_active_amount'    => 11.0,
                                    'quotes_active_new'       => 12,
                                    'quotes_expiring'         => 13,
                                ],
                            ],
                        ]),
                        [
                            'ep.headquarter_type' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        ],
                        static function (TestCase $test, Organization $organization): void {
                            // static function will throw error
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
                                ->create([
                                    'id'                          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'                        => 'name aaa',
                                    'assets_count'                => 0,
                                    'contacts_count'              => 1,
                                    'locations_count'             => 1,
                                    'kpi_assets_total'            => 1,
                                    'kpi_assets_active'           => 2,
                                    'kpi_assets_covered'          => 3.0,
                                    'kpi_customers_active'        => 4,
                                    'kpi_customers_active_new'    => 5,
                                    'kpi_contracts_active'        => 6,
                                    'kpi_contracts_active_amount' => 7.0,
                                    'kpi_contracts_active_new'    => 8,
                                    'kpi_contracts_expiring'      => 9,
                                    'kpi_quotes_active'           => 10,
                                    'kpi_quotes_active_amount'    => 11.0,
                                    'kpi_quotes_active_new'       => 12,
                                    'kpi_quotes_expiring'         => 13,
                                ]);

                            $customer->resellers()->attach(Reseller::factory()->create([
                                'id' => $organization,
                            ]));

                            Location::factory()
                                ->hasTypes(1, [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    'name' => 'headquarter',
                                ])
                                ->create([
                                    'id'          => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                    'state'       => 'state1',
                                    'postcode'    => '19911',
                                    'line_one'    => 'line_one_data',
                                    'line_two'    => 'line_two_data',
                                    'latitude'    => '47.91634204',
                                    'longitude'   => '-2.26318359',
                                    'object_type' => $customer->getMorphClass(),
                                    'object_id'   => $customer->getKey(),
                                ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
