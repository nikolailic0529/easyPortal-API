<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragmentPaginatedSchema;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class CustomerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
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
        $this->setUser($userFactory, $this->setTenant($tenantFactory));
        $this->setSettings($settings);

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this)->id;
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
                        id
                        name
                        assets_count
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
                        contacts_count
                        contacts {
                            name
                            email
                            phone_valid
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
                    }
                }
            ', ['id' => $customerId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryAssets
     *
     */
    public function testQueryAssets(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $tenant = $this->setTenant($tenantFactory);
        $this->setUser($userFactory, $tenant);

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this, $tenant)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
                        assets {
                            data {
                                id
                                oem_id
                                product_id
                                type_id
                                customer_id
                                location_id
                                serial_number
                                customer {
                                    id
                                    name
                                    assets_count
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
                                    contacts_count
                                    contacts {
                                        name
                                        email
                                        phone_valid
                                    }
                                }
                                oem {
                                    id
                                    abbr
                                    name
                                }
                                product {
                                    id
                                    name
                                    oem_id
                                    sku
                                    eol
                                    eos
                                    oem {
                                        id
                                        abbr
                                        name
                                    }
                                }
                                type {
                                    id
                                    name
                                }
                                location {
                                    id
                                    state
                                    postcode
                                    line_one
                                    line_two
                                    latitude
                                    longitude
                                }
                                warranties {
                                    id
                                    asset_id
                                    reseller_id
                                    customer_id
                                    document_id
                                    start
                                    end
                                    note
                                    services {
                                        id
                                        name
                                        oem_id
                                        sku
                                        eol
                                        eos
                                        oem {
                                            id
                                            abbr
                                            name
                                        }
                                    }
                                    package {
                                        id
                                        name
                                        oem_id
                                        sku
                                        eol
                                        eos
                                        oem {
                                            id
                                            abbr
                                            name
                                        }
                                    }
                                    customer {
                                        id
                                        name
                                        assets_count
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
                                        contacts_count
                                        contacts {
                                            name
                                            email
                                            phone_valid
                                        }
                                    }
                                    reseller {
                                        id
                                        name
                                        customers_count
                                        locations_count
                                        assets_count
                                        locations {
                                            id
                                            state
                                            postcode
                                            line_one
                                            line_two
                                            latitude
                                            longitude
                                        }
                                    }
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
                }
            ', ['id' => $customerId])
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQueryAssets(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider('f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
            new UserDataProvider('customer'),
            new ArrayDataProvider([
                'ok'          => [
                    new GraphQLSuccess('customer', new JsonFragmentPaginatedSchema('assets', AssetTest::class), [
                            'assets' => [
                                'data'          => [
                                    [
                                        'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                        'oem_id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'product_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                        'location_id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                        'type_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                                        'customer_id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                        'serial_number' => '#PRODUCT_SERIAL_323',
                                        'customer'      => [
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
                                        ],
                                        'oem'           => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                            'abbr' => 'abbr',
                                            'name' => 'oem1',
                                        ],
                                        'type'          => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                                            'name' => 'name aaa',
                                        ],
                                        'product'       => [
                                            'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                            'name'   => 'Product1',
                                            'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                            'sku'    => 'SKU#123',
                                            'eol'    => '2022-12-30',
                                            'eos'    => '2022-01-01',
                                            'oem'    => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                'abbr' => 'abbr',
                                                'name' => 'oem1',
                                            ],
                                        ],
                                        'location'      => [
                                            'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                            'state'     => 'state1',
                                            'postcode'  => '19911',
                                            'line_one'  => 'line_one_data',
                                            'line_two'  => 'line_two_data',
                                            'latitude'  => '47.91634204',
                                            'longitude' => '-2.26318359',
                                        ],
                                        'warranties'    => [
                                            [
                                                'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                                'asset_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                                'reseller_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                                'customer_id' => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                                'document_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                                'start'       => '2021-01-01',
                                                'end'         => '2022-01-01',
                                                'note'        => 'note',
                                                'customer'    => [
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
                                                ],
                                                'services'    => [
                                                    [
                                                        'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                                        'name'   => 'Product1',
                                                        'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                        'sku'    => 'SKU#123',
                                                        'eol'    => '2022-12-30',
                                                        'eos'    => '2022-01-01',
                                                        'oem'    => [
                                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                            'abbr' => 'abbr',
                                                            'name' => 'oem1',
                                                        ],
                                                    ],
                                                ],
                                                'package'     => [
                                                    'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24998',
                                                    'name'   => 'Product2',
                                                    'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                    'sku'    => 'SKU#321',
                                                    'eol'    => '2022-12-30',
                                                    'eos'    => '2022-01-01',
                                                    'oem'    => [
                                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                        'abbr' => 'abbr',
                                                        'name' => 'oem1',
                                                    ],
                                                ],
                                                'reseller'    => [
                                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                                    'name'            => 'reseller1',
                                                    'customers_count' => 0,
                                                    'locations_count' => 1,
                                                    'assets_count'    => 0,
                                                    'locations'       => [
                                                        [
                                                            'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20954',
                                                            'state'     => 'state2',
                                                            'postcode'  => '19912',
                                                            'line_one'  => 'reseller_one_data',
                                                            'line_two'  => 'reseller_two_data',
                                                            'latitude'  => '49.91634204',
                                                            'longitude' => '90.26318359',
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'paginatorInfo' => [
                                    'count'        => 1,
                                    'currentPage'  => 1,
                                    'firstItem'    => 1,
                                    'hasMorePages' => false,
                                    'lastItem'     => 1,
                                    'lastPage'     => 1,
                                    'perPage'      => 25,
                                    'total'        => 1,
                                ],
                            ],
                        ]),
                    static function (TestCase $test, Organization $organization): Customer {
                        $reseller = Reseller::factory()
                            ->hasLocations(1, [
                                'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20954',
                                'state'     => 'state2',
                                'postcode'  => '19912',
                                'line_one'  => 'reseller_one_data',
                                'line_two'  => 'reseller_two_data',
                                'latitude'  => '49.91634204',
                                'longitude' => '90.26318359',
                            ])
                            ->create([
                                'id'              => $organization->getKey(),
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 1,
                                'assets_count'    => 0,
                            ]);
                        $customer = Customer::factory()
                            ->hasContacts(1, [
                                'name'        => 'contact1',
                                'email'       => 'contact1@test.com',
                                'phone_valid' => false,
                            ])
                            ->hasLocations(1, [
                                'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'     => 'state1',
                                'postcode'  => '19911',
                                'line_one'  => 'line_one_data',
                                'line_two'  => 'line_two_data',
                                'latitude'  => '47.91634204',
                                'longitude' => '-2.26318359',
                            ])
                            ->create([
                                'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'name'            => 'name aaa',
                                'assets_count'    => 0,
                                'contacts_count'  => 1,
                                'locations_count' => 1,
                            ]);
                        $customer->resellers()->attach($reseller);
                        // OEM Creation belongs to
                        $oem = Oem::factory()->create([
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'abbr' => 'abbr',
                            'name' => 'oem1',
                        ]);
                        // Location belongs to
                        $location = Location::factory()->create([
                            'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                            'state'     => 'state1',
                            'postcode'  => '19911',
                            'line_one'  => 'line_one_data',
                            'line_two'  => 'line_two_data',
                            'latitude'  => '47.91634204',
                            'longitude' => '-2.26318359',
                        ]);
                        // Product creation belongs to
                        $product = Product::factory()->create([
                            'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                            'name'   => 'Product1',
                            'oem_id' => $oem->getKey(),
                            'sku'    => 'SKU#123',
                            'eol'    => '2022-12-30',
                            'eos'    => '2022-01-01',
                        ]);
                        // Type Creation belongs to
                        $type = Type::factory()->create([
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                            'name' => 'name aaa',
                        ]);
                        // Product creation for package
                        $product2 = Product::factory()->create([
                            'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24998',
                            'name'   => 'Product2',
                            'oem_id' => $oem->getKey(),
                            'sku'    => 'SKU#321',
                            'eol'    => '2022-12-30',
                            'eos'    => '2022-01-01',
                        ]);
                        // Document creation for package
                        $document = Document::factory()
                            ->for($reseller)
                            ->create([
                                'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                'product_id' => $product2,
                            ]);
                        // Document entry creation for services
                        DocumentEntry::factory()->create([
                            'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                            'document_id' => $document,
                            'asset_id'    => Asset::factory()->create([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                            ]),
                            'product_id'  => $product,
                            'quantity'    => 20,
                        ]);
                        $asset = Asset::factory()
                            ->for($oem)
                            ->for($product)
                            ->for($customer)
                            ->for($type)
                            ->for($location)
                            ->for($reseller)
                            ->create([
                                'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                'serial_number' => '#PRODUCT_SERIAL_323',
                            ]);

                        AssetWarranty::factory()
                            ->hasAttached($product, [], 'services')
                            ->create([
                                'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                'asset_id'    => $asset,
                                'reseller_id' => $reseller,
                                'customer_id' => $customer,
                                'document_id' => $document,
                                'start'       => '2021-01-01',
                                'end'         => '2022-01-01',
                                'note'        => 'note',
                            ]);
                        return $customer;
                    },
                ],
                'not allowed' => [
                    new GraphQLSuccess('customer', null, null),
                    static function (TestCase $test, Organization $organization): Customer {
                        $customer = Customer::factory()->create();
                        return $customer;
                    },
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new UserDataProvider('customer'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('customer', self::class, [
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
                    ]),
                    [
                        'ep.headquarter_type' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                    ],
                    static function (): Customer {
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
                        return $customer;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
