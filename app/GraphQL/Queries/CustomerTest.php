<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Currency;
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
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Tenants\RootTenantDataProvider;
use Tests\DataProviders\GraphQL\Tenants\TenantDataProvider;
use Tests\DataProviders\GraphQL\Users\TenantUserDataProvider;
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
        $tenant = $this->setTenant($tenantFactory);
        $user   = $this->setUser($userFactory, $tenant);

        $this->setSettings($settings);

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this, $tenant, $user)->id;
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
        $user   = $this->setUser($userFactory, $tenant);

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this, $tenant, $user)->getKey();
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

    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderQueryContracts
     *
     * @param array<mixed> $settings
     */
    public function testQueryContracts(
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

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $tenant, $user)->getKey();
        }

        // Not empty?
        if ($expected instanceof GraphQLSuccess) {
            $this->assertGreaterThan(0, Document::query()->count());
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
                        contracts {
                            data {
                                id
                                oem_id
                                product_id
                                type_id
                                customer_id
                                reseller_id
                                number
                                price
                                start
                                end
                                currency_id
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
                                customer {
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
                                currency {
                                    id
                                    name
                                    code
                                }
                                entries {
                                    id
                                    document_id
                                    asset_id
                                    product_id
                                    quantity
                                    net_price
                                    list_price
                                    discount
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

    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderQueryQuotes
     *
     * @param array<mixed> $settings
     */
    public function testQueryQuotes(
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

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $tenant, $user)->getKey();
        }

        // Not empty?
        if ($expected instanceof GraphQLSuccess) {
            $this->assertGreaterThan(0, Document::query()->count());
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
                        quotes {
                            data {
                                id
                                oem_id
                                product_id
                                type_id
                                customer_id
                                reseller_id
                                number
                                price
                                start
                                end
                                currency_id
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
                                customer {
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
                                currency {
                                    id
                                    name
                                    code
                                }
                                entries {
                                    id
                                    document_id
                                    asset_id
                                    product_id
                                    quantity
                                    net_price
                                    list_price
                                    discount
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
        return (new MergeDataProvider([
            'root'   => new CompositeDataProvider(
                new RootTenantDataProvider('customer'),
                new TenantUserDataProvider('customer'),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer', null),
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'tenant' => new CompositeDataProvider(
                new TenantDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
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
                                ->for($customer)
                                ->create([
                                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                    'product_id' => $product2,
                                ]);
                            // Document entry creation for services
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

                            DocumentEntry::factory()->create([
                                'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'document_id' => $document,
                                'asset_id'    => $asset,
                                'product_id'  => $product,
                                'quantity'    => 20,
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
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new MergeDataProvider([
            'root'   => new CompositeDataProvider(
                new RootTenantDataProvider('customer'),
                new TenantUserDataProvider('customer'),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer', null),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'tenant' => new CompositeDataProvider(
                new TenantDataProvider('customer'),
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
                        static function (TestCase $test, Organization $organization): Customer {
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

                            $customer->resellers()->attach(Reseller::factory()->create([
                                'id' => $organization,
                            ]));

                            return $customer;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryContracts(): array {
        $customerContractFactory = static function (TestCase $test, Organization $organization): Customer {
            $reseller = Reseller::factory()->create([
                'id'              => $organization->getKey(),
                'name'            => 'reseller1',
                'customers_count' => 0,
                'locations_count' => 1,
                'assets_count'    => 0,
            ]);
            Document::factory()->for($reseller)->create();
            $customer = Customer::factory()
                ->create([
                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                    'name'            => 'name aaa',
                    'assets_count'    => 0,
                    'contacts_count'  => 0,
                    'locations_count' => 0,
                ]);
            $customer->resellers()->attach($reseller);

            return $customer;
        };

        $customerEmptyContract = [
            'contracts' => [
                'data'          => [
                    // empty
                ],
                'paginatorInfo' => [
                    'count'        => 0,
                    'currentPage'  => 1,
                    'firstItem'    => null,
                    'hasMorePages' => false,
                    'lastItem'     => null,
                    'lastPage'     => 1,
                    'perPage'      => 25,
                    'total'        => 0,
                ],
            ],
        ];

        return (new MergeDataProvider([
            'root'   => new CompositeDataProvider(
                new RootTenantDataProvider('customer'),
                new TenantUserDataProvider('customer'),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer', null),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            Document::factory()->create();

                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'tenant' => new CompositeDataProvider(
                new TenantDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new UserDataProvider('customer'),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('contracts', ContractsTest::class),
                            [
                                'contracts' => [
                                    'data'          => [
                                        [
                                            'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                            'oem_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                            'product_id'  => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                            'customer_id' => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                            'type_id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                            'reseller_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                            'currency_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                            'number'      => '1323',
                                            'price'       => '100.00',
                                            'start'       => '2021-01-01',
                                            'end'         => '2024-01-01',
                                            'oem'         => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                'abbr' => 'abbr',
                                                'name' => 'oem1',
                                            ],
                                            'product'     => [
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
                                            'type'        => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                                'name' => 'name aaa',
                                            ],
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
                                            'reseller'    => [
                                                'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
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
                                            'currency'    => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                                'name' => 'Currency1',
                                                'code' => 'CUR',
                                            ],
                                            'entries'     => [
                                                [
                                                    'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                                    'asset_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                                    'product_id'  => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                                    'document_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                                    'quantity'    => 20,
                                                    'net_price'   => '123.00',
                                                    'list_price'  => '67.12',
                                                    'discount'    => null,
                                                    'product'     => [
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
                            ],
                        ),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): Customer {
                            // Reseller creation belongs to
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
                                ->hasLocations([
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
                            // Type Creation belongs to
                            $type = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
                            ]);
                            // Product creation belongs to
                            $product = Product::factory()->create([
                                'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                'name'   => 'Product1',
                                'oem_id' => $oem,
                                'sku'    => 'SKU#123',
                                'eol'    => '2022-12-30',
                                'eos'    => '2022-01-01',
                            ]);
                            // Currency creation belongs to
                            $currency = Currency::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'name' => 'Currency1',
                                'code' => 'CUR',
                            ]);
                            Document::factory()
                                ->for($oem)
                                ->for($product)
                                ->for($customer)
                                ->for($type)
                                ->for($reseller)
                                ->for($currency)
                                ->hasEntries(1, [
                                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                    'asset_id'   => Asset::factory()->create([
                                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                    ]),
                                    'product_id' => $product,
                                    'quantity'   => 20,
                                    'net_price'  => '123',
                                    'list_price' => '67.12',
                                    'discount'   => null,
                                ])
                                ->create([
                                    'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'number' => '1323',
                                    'price'  => '100',
                                    'start'  => '2021-01-01',
                                    'end'    => '2024-01-01',
                                ]);

                            return $customer;
                        },
                    ],
                    'no types'       => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('contracts', ContractsTest::class),
                            $customerEmptyContract,
                        ),
                        [
                            'ep.contract_types' => [
                                // empty
                            ],
                        ],
                        $customerContractFactory,
                    ],
                    'type not match' => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('contracts', ContractsTest::class),
                            $customerEmptyContract,
                        ),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $customerContractFactory,
                    ],
                    'not allowed'    => [
                        new GraphQLSuccess('customer', null, null),
                        [
                            'ep.contract_types' => [
                                // empty
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): Customer {
                            $reseller = Reseller::factory()->create([
                                'id'              => $organization->getKey(),
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 1,
                                'assets_count'    => 0,
                            ]);
                            Document::factory()->for($reseller)->create();
                            $customer = Customer::factory()
                                ->create([
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 0,
                                    'contacts_count'  => 0,
                                    'locations_count' => 0,
                                ]);

                            return $customer;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryQuotes(): array {
        $customerQuoteEmptyFactory = static function (TestCase $test, Organization $organization): Customer {
            $reseller = Reseller::factory()->create([
                'id'              => $organization->getKey(),
                'name'            => 'reseller1',
                'customers_count' => 0,
                'locations_count' => 1,
                'assets_count'    => 0,
            ]);
            $type     = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name aaa',
            ]);
            Document::factory()
                ->for($reseller)
                ->for($type)
                ->create();
            $customer = Customer::factory()
                ->create([
                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                    'name'            => 'name aaa',
                    'assets_count'    => 0,
                    'contacts_count'  => 0,
                    'locations_count' => 0,
                ]);
            $customer->resellers()->attach($reseller);

            return $customer;
        };

        $customerQuoteFactory = static function (TestCase $test, Organization $organization): Customer {
            // Reseller creation belongs to
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
                ->hasLocations([
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
            // Type Creation belongs to
            $type = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name aaa',
            ]);
            // Product creation belongs to
            $product = Product::factory()->create([
                'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                'name'   => 'Product1',
                'oem_id' => $oem,
                'sku'    => 'SKU#123',
                'eol'    => '2022-12-30',
                'eos'    => '2022-01-01',
            ]);
            // Currency creation belongs to
            $currency = Currency::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                'name' => 'Currency1',
                'code' => 'CUR',
            ]);
            Document::factory()
                ->for($oem)
                ->for($product)
                ->for($customer)
                ->for($type)
                ->for($reseller)
                ->for($currency)
                ->hasEntries(1, [
                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    'asset_id'   => Asset::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                    ]),
                    'product_id' => $product,
                    'quantity'   => 20,
                    'net_price'  => '123',
                    'list_price' => '67.12',
                    'discount'   => null,
                ])
                ->create([
                    'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'number' => '1323',
                    'price'  => '100',
                    'start'  => '2021-01-01',
                    'end'    => '2024-01-01',
                ]);

            return $customer;
        };
        $customerQuote        = [
            'quotes' => [
                'data'          => [
                    [
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                        'oem_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'product_id'  => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                        'customer_id' => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        'type_id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        'reseller_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                        'currency_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                        'number'      => '1323',
                        'price'       => '100.00',
                        'start'       => '2021-01-01',
                        'end'         => '2024-01-01',
                        'oem'         => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'abbr' => 'abbr',
                            'name' => 'oem1',
                        ],
                        'product'     => [
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
                        'type'        => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            'name' => 'name aaa',
                        ],
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
                        'reseller'    => [
                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
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
                        'currency'    => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                            'name' => 'Currency1',
                            'code' => 'CUR',
                        ],
                        'entries'     => [
                            [
                                'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'asset_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                'product_id'  => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                'document_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                'quantity'    => 20,
                                'net_price'   => '123.00',
                                'list_price'  => '67.12',
                                'discount'    => null,
                                'product'     => [
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
        ];
        $customerEmptyQuote   = [
            'quotes' => [
                'data'          => [
                    // empty
                ],
                'paginatorInfo' => [
                    'count'        => 0,
                    'currentPage'  => 1,
                    'firstItem'    => null,
                    'hasMorePages' => false,
                    'lastItem'     => null,
                    'lastPage'     => 1,
                    'perPage'      => 25,
                    'total'        => 0,
                ],
            ],
        ];

        return (new MergeDataProvider([
            'root'   => new CompositeDataProvider(
                new RootTenantDataProvider('customer'),
                new TenantUserDataProvider('customer'),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer', null),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            Document::factory()->create();

                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'tenant' => new CompositeDataProvider(
                new TenantDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new UserDataProvider('customer'),
                new ArrayDataProvider([
                    'ok'                                        => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('quotes', QuotesTest::class),
                            $customerQuote,
                        ),
                        [
                            'ep.quote_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $customerQuoteFactory,
                    ],
                    'not allowed'                               => [
                        new GraphQLSuccess('customer', null, null),
                        [
                            'ep.contract_types' => [
                                // empty
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): Customer {
                            $reseller = Reseller::factory()->create([
                                'id'              => $organization->getKey(),
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 1,
                                'assets_count'    => 0,
                            ]);
                            Document::factory()->for($reseller)->create();
                            $customer = Customer::factory()
                                ->create([
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 0,
                                    'contacts_count'  => 0,
                                    'locations_count' => 0,
                                ]);

                            return $customer;
                        },
                    ],
                    'no quote_types + contract_types not match' => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('quotes', QuotesTest::class),
                            $customerQuote,
                        ),
                        [
                            'ep.contract_types' => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                        ],
                        $customerQuoteFactory,
                    ],
                    'no quote_types + contract_types match'     => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('quotes', QuotesTest::class),
                            $customerEmptyQuote,
                        ),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $customerQuoteEmptyFactory,
                    ],
                    'quote_types not match'                     => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('quotes', QuotesTest::class),
                            $customerEmptyQuote,
                        ),
                        [
                            'ep.quote_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac2498a',
                            ],
                        ],
                        $customerQuoteEmptyFactory,
                    ],
                    'no quote_types + no contract_types'        => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('quotes', QuotesTest::class),
                            $customerEmptyQuote,
                        ),
                        [
                            'ep.quote_types' => [
                                // empty
                            ],
                        ],
                        $customerQuoteEmptyFactory,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
