<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Language;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Status;
use App\Models\Type;
use App\Models\User;
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
use Tests\GraphQL\JsonFragmentPaginatedSchema;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Customer
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
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $this->setSettings($settings);

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this, $organization, $user)->id;
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
                        statuses {
                          id
                          key
                          name
                        }
                    }
                }
            ', ['id' => $customerId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryAssets
     *
     * @param array<string, mixed> $settings
     */
    public function testQueryAssets(
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

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this, $organization, $user)->getKey();
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
                                data_quality
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
                                    key
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
                                        key
                                        name
                                    }
                                }
                                type {
                                    id
                                    name
                                }
                                status {
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
                                    reseller_id
                                    customer_id
                                    document_id
                                    start
                                    end
                                    note
                                    serviceGroup {
                                        id
                                        oem_id
                                        sku
                                        name
                                    }
                                    serviceLevels {
                                        id
                                        oem_id
                                        service_group_id
                                        sku
                                        name
                                        description
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
                                contacts_count
                                contacts {
                                    name
                                    email
                                    phone_valid
                                }
                                status {
                                    id
                                    name
                                }
                                coverages {
                                    id
                                    name
                                }
                                tags {
                                    id
                                    name
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
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $this->setSettings($settings);

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $organization, $user)->getKey();
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
                                service_group_id
                                type_id
                                is_contract
                                is_quote
                                customer_id
                                reseller_id
                                number
                                price
                                start
                                end
                                currency_id
                                language_id
                                distributor_id
                                oem {
                                    id
                                    key
                                    name
                                }
                                oem_said
                                oemGroup {
                                    id
                                    key
                                    name
                                }
                                serviceGroup {
                                    id
                                    oem_id
                                    sku
                                    name
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
                                    service_level_id
                                    net_price
                                    list_price
                                    discount
                                    renewal
                                    serial_number
                                    product_id
                                    product {
                                        id
                                        name
                                        oem_id
                                        sku
                                        eol
                                        eos
                                        oem {
                                            id
                                            key
                                            name
                                        }
                                    }
                                    serviceLevel {
                                        id
                                        oem_id
                                        service_group_id
                                        sku
                                        name
                                        description
                                    }
                                }
                                language {
                                    id
                                    name
                                    code
                                }
                                contacts {
                                    name
                                    email
                                    phone_valid
                                }
                                distributor {
                                    id
                                    name
                                }
                                assets_count
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
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $this->setSettings($settings);

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $organization, $user)->getKey();
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
                                service_group_id
                                type_id
                                is_contract
                                is_quote
                                customer_id
                                reseller_id
                                number
                                price
                                start
                                end
                                currency_id
                                language_id
                                distributor_id
                                oem {
                                    id
                                    key
                                    name
                                }
                                oem_said
                                oemGroup {
                                    id
                                    key
                                    name
                                }
                                serviceGroup {
                                    id
                                    oem_id
                                    sku
                                    name
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
                                    service_level_id
                                    net_price
                                    list_price
                                    discount
                                    renewal
                                    serial_number
                                    product_id
                                    product {
                                        id
                                        name
                                        oem_id
                                        sku
                                        eol
                                        eos
                                        oem {
                                            id
                                            key
                                            name
                                        }
                                    }
                                    serviceLevel {
                                        id
                                        oem_id
                                        service_group_id
                                        sku
                                        name
                                        description
                                    }
                                }
                                language {
                                    id
                                    name
                                    code
                                }
                                contacts {
                                    name
                                    email
                                    phone_valid
                                }
                                distributor {
                                    id
                                    name
                                }
                                assets_count
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
     * @covers ::assetsAggregate
     *
     * @dataProvider dataProviderQueryAssetAggregate
     *
     * @param array<string, mixed> $params
     */
    public function testQueryAssetAggregate(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $customerFactory = null,
        array $params = [],
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $organization, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!, $where: SearchByConditionAssetsQuery) {
                    customer(id: $id) {
                        assetsAggregate(where: $where) {
                            count
                            types {
                                count
                                type_id
                                type {
                                    id
                                    key
                                    name
                                }
                            }
                            coverages {
                                count
                                coverage_id
                                coverage {
                                    id
                                    key
                                    name
                                }
                            }
                        }
                    }
                }
            ', ['id' => $customerId] + $params)
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
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('customer'),
                new OrganizationUserDataProvider('customer', [
                    'customers-view',
                ]),
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
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
                new OrganizationUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok'          => [
                        new GraphQLSuccess('customer', new JsonFragmentPaginatedSchema('assets', AssetTest::class), [
                            'assets' => [
                                'data'          => [
                                    [
                                        'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                        'oem_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'product_id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                        'location_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                        'type_id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                                        'customer_id'    => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                        'serial_number'  => '#PRODUCT_SERIAL_323',
                                        'data_quality'   => '130',
                                        'contacts_count' => 1,
                                        'customer'       => [
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
                                        'oem'            => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                            'key'  => 'key',
                                            'name' => 'oem1',
                                        ],
                                        'type'           => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                                            'name' => 'name aaa',
                                        ],
                                        'product'        => [
                                            'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                            'name'   => 'Product1',
                                            'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                            'sku'    => 'SKU#123',
                                            'eol'    => '2022-12-30',
                                            'eos'    => '2022-01-01',
                                            'oem'    => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                'key'  => 'key',
                                                'name' => 'oem1',
                                            ],
                                        ],
                                        'location'       => [
                                            'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                            'state'     => 'state1',
                                            'postcode'  => '19911',
                                            'line_one'  => 'line_one_data',
                                            'line_two'  => 'line_two_data',
                                            'latitude'  => 47.91634204,
                                            'longitude' => -2.26318359,
                                        ],
                                        'warranties'     => [
                                            [
                                                'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                                'reseller_id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                                'customer_id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                                'document_id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                                'start'         => '2021-01-01',
                                                'end'           => '2022-01-01',
                                                'note'          => 'note',
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
                                                'serviceLevels' => [
                                                    [
                                                        'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                                        'name'             => 'Level',
                                                        'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                                        'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                        'sku'              => 'SKU#123',
                                                        'description'      => 'description',
                                                    ],
                                                ],
                                                'serviceGroup'  => [
                                                    'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                                    'name'   => 'Group',
                                                    'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                    'sku'    => 'SKU#123',
                                                ],
                                                'reseller'      => [
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
                                                            'latitude'  => 49.91634204,
                                                            'longitude' => 90.26318359,
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        'contacts'       => [
                                            [
                                                'name'        => 'contact2',
                                                'email'       => 'contact2@test.com',
                                                'phone_valid' => false,
                                            ],
                                        ],
                                        'status'         => [
                                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                            'name' => 'active',
                                        ],
                                        'coverages'      => [
                                            [
                                                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20948',
                                                'name' => 'COVERED_ON_CONTRACT',
                                            ],
                                        ],
                                        'tags'           => [
                                            [
                                                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20950',
                                                'name' => 'Software',
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
                        [
                            'ep.contract_types' => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                            ],
                        ],
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
                                'key'  => 'key',
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
                            // Status Creation belongs to
                            $status = Status::factory()->create([
                                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                'name'        => 'active',
                                'key'         => 'active',
                                'object_type' => (new Asset())->getMorphClass(),
                            ]);

                            // Service Group/Level
                            $serviceGroup = ServiceGroup::factory()->create([
                                'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                'oem_id' => $oem,
                                'sku'    => 'SKU#123',
                                'name'   => 'Group',
                            ]);
                            $serviceLevel = ServiceLevel::factory()->create([
                                'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                'oem_id'           => $oem,
                                'service_group_id' => $serviceGroup,
                                'sku'              => 'SKU#123',
                                'name'             => 'Level',
                                'description'      => 'description',
                            ]);

                            // Document creation for support
                            $documentType = Type::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                            ]);
                            $document     = Document::factory()
                                ->for($reseller)
                                ->for($customer)
                                ->create([
                                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                    'type_id'          => $documentType,
                                    'service_group_id' => $serviceGroup,
                                ]);
                            // Asset creation
                            $asset = Asset::factory()
                                ->for($oem)
                                ->for($product)
                                ->for($customer)
                                ->for($type)
                                ->for($status)
                                ->for($location)
                                ->for($reseller)
                                ->for($status)
                                ->hasTags(1, [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20950',
                                    'name' => 'Software',
                                ])
                                ->hasContacts(1, [
                                    'name'        => 'contact2',
                                    'email'       => 'contact2@test.com',
                                    'phone_valid' => false,
                                ])
                                ->hasCoverages(1, [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20948',
                                    'name' => 'COVERED_ON_CONTRACT',
                                ])
                                ->create([
                                    'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'serial_number'  => '#PRODUCT_SERIAL_323',
                                    'contacts_count' => 1,
                                    'data_quality'   => '130',
                                ]);
                            // Document entry creation for services
                            DocumentEntry::factory()->create([
                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'document_id'      => $document,
                                'asset_id'         => $asset,
                                'product_id'       => $product,
                                'service_level_id' => $serviceLevel,
                            ]);

                            AssetWarranty::factory()
                                ->hasAttached($serviceLevel)
                                ->for($serviceGroup)
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
                        [],
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
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('customer'),
                new OrganizationUserDataProvider('customer', [
                    'customers-view',
                ]),
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
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('customer'),
                new OrganizationUserDataProvider('customer', [
                    'customers-view',
                ]),
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
                                ->hasStatuses(1, [
                                    'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                    'name'        => 'active',
                                    'key'         => 'active',
                                    'object_type' => (new Reseller())->getMorphClass(),
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
                                    'latitude'    => 47.91634204,
                                    'longitude'   => -2.26318359,
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
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('customer'),
                new OrganizationUserDataProvider('customer', [
                    'customers-view',
                ]),
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
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrganizationUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLSuccess(
                            'customer',
                            new JsonFragmentPaginatedSchema('contracts', ContractsTest::class),
                            [
                                'contracts' => [
                                    'data'          => [
                                        [
                                            'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                            'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                            'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                            'customer_id'      => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                            'type_id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                            'reseller_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                            'currency_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                            'language_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                                            'distributor_id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                                            'is_contract'      => true,
                                            'is_quote'         => false,
                                            'number'           => '1323',
                                            'price'            => 100,
                                            'start'            => '2021-01-01',
                                            'end'              => '2024-01-01',
                                            'oem'              => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                'key'  => 'key',
                                                'name' => 'oem1',
                                            ],
                                            'oem_said'         => null,
                                            'oemGroup'         => null,
                                            'serviceGroup'     => [
                                                'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                                'name'   => 'Group',
                                                'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                'sku'    => 'SKU#123',
                                            ],
                                            'type'             => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                                'name' => 'name aaa',
                                            ],
                                            'customer'         => [
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
                                            'reseller'         => [
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
                                                        'latitude'  => 49.91634204,
                                                        'longitude' => 90.26318359,
                                                    ],
                                                ],
                                            ],
                                            'currency'         => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                                'name' => 'Currency1',
                                                'code' => 'CUR',
                                            ],
                                            'entries'          => [
                                                [
                                                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                                    'service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                                    'document_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                                    'net_price'        => 123,
                                                    'list_price'       => 67.12,
                                                    'discount'         => null,
                                                    'renewal'          => 24.20,
                                                    'serial_number'    => null,
                                                    'product_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                                    'product'          => [
                                                        'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                                        'name'   => 'Product1',
                                                        'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                        'sku'    => 'SKU#123',
                                                        'eol'    => '2022-12-30',
                                                        'eos'    => '2022-01-01',
                                                        'oem'    => [
                                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                            'key'  => 'key',
                                                            'name' => 'oem1',
                                                        ],
                                                    ],
                                                    'serviceLevel'     => [
                                                        'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                                        'name'             => 'Level',
                                                        'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                                        'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                        'sku'              => 'SKU#123',
                                                        'description'      => 'description',
                                                    ],
                                                ],
                                            ],
                                            'language'         => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                                                'name' => 'Lang1',
                                                'code' => 'en',
                                            ],
                                            'contacts'         => [
                                                [
                                                    'name'        => 'contact3',
                                                    'email'       => 'contact3@test.com',
                                                    'phone_valid' => false,
                                                ],
                                            ],
                                            'distributor'      => [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                                                'name' => 'distributor1',
                                            ],
                                            'assets_count'     => 1,
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
                        static function (TestCase $test, Organization $organization, User $user): Customer {
                            $user->save();
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
                                'key'  => 'key',
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
                            // Language creation belongs to
                            $language = Language::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                                'name' => 'Lang1',
                                'code' => 'en',
                            ]);
                            // Distributor
                            $distributor  = Distributor::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                                'name' => 'distributor1',
                            ]);
                            $serviceGroup = ServiceGroup::factory()->create([
                                'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                'oem_id' => $oem,
                                'sku'    => 'SKU#123',
                                'name'   => 'Group',
                            ]);
                            $serviceLevel = ServiceLevel::factory()->create([
                                'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                'oem_id'           => $oem,
                                'service_group_id' => $serviceGroup,
                                'sku'              => 'SKU#123',
                                'name'             => 'Level',
                                'description'      => 'description',
                            ]);

                            Document::factory()
                                ->for($oem)
                                ->for($serviceGroup)
                                ->for($customer)
                                ->for($type)
                                ->for($reseller)
                                ->for($currency)
                                ->for($language)
                                ->for($distributor)
                                ->hasEntries(1, [
                                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                    'asset_id'         => Asset::factory()->create([
                                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                    ]),
                                    'serial_number'    => null,
                                    'product_id'       => $product,
                                    'service_level_id' => $serviceLevel,
                                    'net_price'        => 123,
                                    'list_price'       => 67.12,
                                    'discount'         => null,
                                    'renewal'          => 24.20,
                                ])
                                ->hasContacts(1, [
                                    'name'        => 'contact3',
                                    'email'       => 'contact3@test.com',
                                    'phone_valid' => false,
                                ])
                                ->create([
                                    'id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'oem_said'     => null,
                                    'number'       => '1323',
                                    'price'        => 100,
                                    'start'        => '2021-01-01',
                                    'end'          => '2024-01-01',
                                    'assets_count' => 1,
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

        $customerQuoteFactory = static function (TestCase $test, Organization $organization, User $user): Customer {
            $user->save();
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
                'key'  => 'key',
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
            // Language creation belongs to
            $language = Language::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                'name' => 'Lang1',
                'code' => 'en',
            ]);
            // Distributor
            $distributor  = Distributor::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                'name' => 'distributor1',
            ]);
            $serviceGroup = ServiceGroup::factory()->create([
                'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                'oem_id' => $oem,
                'sku'    => 'SKU#123',
                'name'   => 'Group',
            ]);
            $serviceLevel = ServiceLevel::factory()->create([
                'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                'oem_id'           => $oem,
                'service_group_id' => $serviceGroup,
                'sku'              => 'SKU#123',
                'name'             => 'Level',
                'description'      => 'description',
            ]);

            Document::factory()
                ->for($oem)
                ->for($serviceGroup)
                ->for($customer)
                ->for($type)
                ->for($reseller)
                ->for($currency)
                ->for($language)
                ->for($distributor)
                ->hasEntries(1, [
                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    'asset_id'         => Asset::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                    ]),
                    'serial_number'    => null,
                    'product_id'       => $product,
                    'service_level_id' => $serviceLevel,
                    'net_price'        => 123,
                    'list_price'       => 67.12,
                    'discount'         => null,
                    'renewal'          => 24.20,
                ])
                ->hasContacts(1, [
                    'name'        => 'contact3',
                    'email'       => 'contact3@test.com',
                    'phone_valid' => false,
                ])
                ->create([
                    'id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'oem_said'     => '225d982d-926d-3db4-ba0b-81ab17b790b0',
                    'number'       => '1323',
                    'price'        => 100,
                    'start'        => '2021-01-01',
                    'end'          => '2024-01-01',
                    'assets_count' => 1,
                ]);

            return $customer;
        };
        $customerQuote        = [
            'quotes' => [
                'data'          => [
                    [
                        'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                        'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                        'customer_id'      => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        'type_id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        'reseller_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                        'currency_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                        'language_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                        'distributor_id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                        'is_contract'      => false,
                        'is_quote'         => true,
                        'number'           => '1323',
                        'price'            => 100,
                        'start'            => '2021-01-01',
                        'end'              => '2024-01-01',
                        'oem'              => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'key'  => 'key',
                            'name' => 'oem1',
                        ],
                        'oem_said'         => '225d982d-926d-3db4-ba0b-81ab17b790b0',
                        'oemGroup'         => null,
                        'serviceGroup'     => [
                            'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'name'   => 'Group',
                            'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'sku'    => 'SKU#123',
                        ],
                        'type'             => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            'name' => 'name aaa',
                        ],
                        'customer'         => [
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
                        'reseller'         => [
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
                                    'latitude'  => 49.91634204,
                                    'longitude' => 90.26318359,
                                ],
                            ],
                        ],
                        'currency'         => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                            'name' => 'Currency1',
                            'code' => 'CUR',
                        ],
                        'entries'          => [
                            [
                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                'document_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                'net_price'        => 123.00,
                                'list_price'       => 67.12,
                                'discount'         => null,
                                'renewal'          => 24.20,
                                'serial_number'    => null,
                                'product_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                'product'          => [
                                    'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                    'name'   => 'Product1',
                                    'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'sku'    => 'SKU#123',
                                    'eol'    => '2022-12-30',
                                    'eos'    => '2022-01-01',
                                    'oem'    => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'key'  => 'key',
                                        'name' => 'oem1',
                                    ],
                                ],
                                'serviceLevel'     => [
                                    'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                    'name'             => 'Level',
                                    'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                    'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'sku'              => 'SKU#123',
                                    'description'      => 'description',
                                ],
                            ],
                        ],
                        'language'         => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                            'name' => 'Lang1',
                            'code' => 'en',
                        ],
                        'contacts'         => [
                            [
                                'name'        => 'contact3',
                                'email'       => 'contact3@test.com',
                                'phone_valid' => false,
                            ],
                        ],
                        'distributor'      => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                            'name' => 'distributor1',
                        ],
                        'assets_count'     => 1,
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
            'root'         => new CompositeDataProvider(
                new RootOrganizationDataProvider('customer'),
                new OrganizationUserDataProvider('customer', [
                    'customers-view',
                ]),
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
            'organization' => new CompositeDataProvider(
                new OrganizationDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrganizationUserDataProvider('customer', [
                    'customers-view',
                ]),
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

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryAssetAggregate(): array {
        $factory = static function (TestCase $test, Organization $organization): Customer {
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
            // Type
            $type  = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name1',
                'key'  => 'key1',
            ]);
            $type2 = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                'name' => 'name2',
                'key'  => 'key2',
            ]);
            // Assets
            Asset::factory()
                ->hasCoverages(1, [
                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'name' => 'name2',
                    'key'  => 'key2',
                ])
                ->create([
                    'type_id'     => $type,
                    'reseller_id' => $reseller,
                    'customer_id' => $customer,
                ]);
            Asset::factory()->create([
                'type_id'     => $type,
                'reseller_id' => $reseller,
                'customer_id' => $customer,
            ]);
            Asset::factory()->create([
                'type_id'     => $type2,
                'reseller_id' => $reseller,
                'customer_id' => $customer,
            ]);
            // Another customer
            $customer2 = Customer::factory()->create();
            Asset::factory()->create([
                'type_id'     => $type2,
                'customer_id' => $customer2,
            ]);

            return $customer;
        };
        $params  = [
            'where' => [
                'type_id' => [
                    'in' => [
                        'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                    ],
                ],
            ],
        ];

        return (new CompositeDataProvider(
            new OrganizationDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
            new OrganizationUserDataProvider('customer', [
                'customers-view',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'customer',
                        new JsonFragmentSchema('assetsAggregate', AssetsAggregate::class),
                        [
                            'assetsAggregate' => [
                                'count'     => 3,
                                'types'     => [
                                    [
                                        'count'   => 2,
                                        'type_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                        'type'    => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                            'name' => 'name1',
                                            'key'  => 'key1',
                                        ],
                                    ],
                                    [
                                        'count'   => 1,
                                        'type_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                        'type'    => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                            'name' => 'name2',
                                            'key'  => 'key2',
                                        ],
                                    ],
                                ],
                                'coverages' => [
                                    [
                                        'count'       => 1,
                                        'coverage_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                        'coverage'    => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                            'name' => 'name2',
                                            'key'  => 'key2',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ),
                    $factory,
                    $params,
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
