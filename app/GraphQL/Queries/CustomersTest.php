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
use Tests\DataProviders\GraphQL\Tenants\RootTenantDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\TenantUserDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
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
        Closure $tenantFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $tenant = $this->setTenant($tenantFactory);
        $user   = $this->setUser($userFactory, $tenant);

        $this->setSettings($settings);

        if ($customerFactory) {
            $customerFactory($this, $tenant, $user);
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
            'root'   => new CompositeDataProvider(
                new RootTenantDataProvider('customers'),
                new TenantUserDataProvider('customers'),
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
            'tenant' => new CompositeDataProvider(
                new TenantDataProvider('customers'),
                new UserDataProvider('customers'),
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
                                        'latitude'  => '47.91634204',
                                        'longitude' => '-2.26318359',
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
                                    'latitude'  => '47.91634204',
                                    'longitude' => '-2.26318359',
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
