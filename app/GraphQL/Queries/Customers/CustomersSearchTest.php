<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

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
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;
use Tests\WithScout;

/**
 * @coversNothing
 * @internal
 */
class CustomersSearchTest extends TestCase {
    use WithScout;

    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $factory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($factory) {
            $this->makeSearchable($factory($this, $organization, $user));
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '{
            customersSearch(search: "*") {
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
                    contacts {
                        name
                        email
                        phone_valid
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
                new RootOrganizationDataProvider('customersSearch'),
                new OrganizationUserDataProvider('customersSearch', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('customersSearch', null),
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('customersSearch'),
                new UserDataProvider('customersSearch', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('customersSearch', self::class, [
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
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization): Customer {
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
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 0,
                                    'contacts_count'  => 1,
                                    'locations_count' => 1,
                                ]);

                            $customer->resellers()->attach(Reseller::factory()->create([
                                'id' => $organization,
                            ]));

                            Location::factory()->create([
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

                            return $customer;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>

}
