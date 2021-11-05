<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Documents;

use App\Models\Asset;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Language;
use App\Models\Location;
use App\Models\Oem;
use App\Models\OemGroup;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
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
use Tests\GraphQL\GraphQLPaginated;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Documents\Documents
 */
class DocumentsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     *
     * @dataProvider dataProviderQuery
     *
     * @param array<mixed> $settings
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = [],
        Closure $documentsFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($settings) {
            $this->setSettings($settings);
        }

        if ($documentsFactory) {
            $documentsFactory($this, $organization, $user);
        }

        // Not empty?
        if ($expected instanceof GraphQLSuccess) {
            $this->assertGreaterThan(0, Document::query()->count());
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    documents(order: [{id: asc}]) {
                        id
                        oem_id
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
                        type {
                            id
                            name
                        }
                        statuses {
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
                            changed_at
                            synced_at
                        }
                        reseller {
                            id
                            name
                            customers_count
                            locations_count
                            assets_count
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
                            service_group_id
                            serviceGroup {
                                id
                                oem_id
                                sku
                                name
                            }
                            serviceLevel {
                                id
                                oem_id
                                service_group_id
                                sku
                                name
                                description
                            }
                            asset_id
                            asset {
                                id
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
                        changed_at
                        synced_at
                    }
                    documentsAggregated {
                        count
                    }
                }
            ')
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        $factory  = static function (TestCase $test, Organization $organization, User $user): void {
            // OEM Creation belongs to
            $oem      = Oem::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'key'  => 'key',
                'name' => 'oem1',
            ]);
            $oemGroup = OemGroup::factory()->create([
                'id'   => '52f2faec-5a80-4cdb-8cee-669b942ae1ef',
                'key'  => 'key',
                'name' => 'name',
            ]);
            // Type Creation belongs to
            $typeA = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name aaa',
            ]);
            $typeB = Type::factory()->create([
                'id'   => 'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                'name' => 'name bbb',
            ]);

            // Location
            $location = Location::factory()->create([
                'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                'state'     => 'state1',
                'postcode'  => '19911',
                'line_one'  => 'line_one_data',
                'line_two'  => 'line_two_data',
                'latitude'  => '47.91634204',
                'longitude' => '-2.26318359',
            ]);

            // Reseller creation belongs to
            $reseller = Reseller::factory()->create([
                'id'              => $organization,
                'name'            => 'reseller1',
                'customers_count' => 0,
                'locations_count' => 1,
                'assets_count'    => 0,
            ]);

            ResellerLocation::factory()->create([
                'reseller_id' => $reseller,
                'location_id' => $location,
            ]);

            $location->resellers()->attach($reseller);

            // Customer Creation creation belongs to
            $customer = Customer::factory()
                ->hasContacts(1, [
                    'name'        => 'contact1',
                    'email'       => 'contact1@test.com',
                    'phone_valid' => false,
                ])
                ->create([
                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                    'name'            => 'name aaa',
                    'assets_count'    => 2,
                    'locations_count' => 1,
                    'contacts_count'  => 2,
                    'changed_at'      => '2021-10-19 10:15:00',
                    'synced_at'       => '2021-10-19 10:25:00',
                ]);

            CustomerLocation::factory()->create([
                'customer_id' => $customer,
                'location_id' => $location,
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
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
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
                ->for($oemGroup)
                ->for($customer)
                ->for($typeA)
                ->for($reseller)
                ->for($currency)
                ->for($language)
                ->for($distributor)
                ->hasContacts(1, [
                    'name'        => 'contact2',
                    'email'       => 'contact2@test.com',
                    'phone_valid' => false,
                ])
                ->hasEntries(1, [
                    'id'               => '7294133d-33d4-4c0d-94bb-b012a5b0512c',
                    'asset_id'         => Asset::factory()->create([
                        'id'          => '8da0cf0e-2d59-4ab7-b5e5-6d3681e98429',
                        'reseller_id' => $reseller,
                    ]),
                    'serial_number'    => null,
                    'product_id'       => $product,
                    'service_group_id' => $serviceGroup,
                    'service_level_id' => $serviceLevel,
                    'net_price'        => 123.45,
                    'list_price'       => 67.00,
                    'discount'         => -8,
                    'renewal'          => 24.20,
                ])
                ->create([
                    'id'           => '323feb6f-dff3-4eb7-a2d6-b064139be2dd',
                    'oem_said'     => '1234-5678-9012',
                    'number'       => '1323',
                    'price'        => 100,
                    'start'        => '2021-01-01',
                    'end'          => '2024-01-01',
                    'assets_count' => 1,
                    'changed_at'   => '2021-10-19 10:15:00',
                    'synced_at'    => '2021-10-19 10:25:00',
                ]);

            Document::factory()
                ->for($oem)
                ->for($oemGroup)
                ->for($customer)
                ->for($typeB)
                ->for($reseller)
                ->for($currency)
                ->for($language)
                ->for($distributor)
                ->hasContacts(1, [
                    'name'        => 'contact2',
                    'email'       => 'contact2@test.com',
                    'phone_valid' => false,
                ])
                ->hasEntries(1, [
                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    'asset_id'         => Asset::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                        'reseller_id' => $reseller,
                    ]),
                    'serial_number'    => null,
                    'product_id'       => $product,
                    'service_group_id' => $serviceGroup,
                    'service_level_id' => $serviceLevel,
                    'net_price'        => 123.45,
                    'list_price'       => 67.00,
                    'discount'         => -8,
                    'renewal'          => 24.20,
                ])
                ->create([
                    'id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'oem_said'     => '1234-5678-9012',
                    'number'       => '1323',
                    'price'        => 100,
                    'start'        => '2021-01-01',
                    'end'          => '2024-01-01',
                    'assets_count' => 1,
                    'changed_at'   => '2021-10-19 10:15:00',
                    'synced_at'    => '2021-10-19 10:25:00',
                ]);

            $customer->resellers()->attach($reseller, [
                'assets_count'    => 0,
                'locations_count' => 1,
            ]);
        };
        $quote    = [
            'id'             => '323feb6f-dff3-4eb7-a2d6-b064139be2dd',
            'oem_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
            'customer_id'    => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
            'type_id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
            'reseller_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
            'currency_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
            'language_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
            'distributor_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
            'is_contract'    => false,
            'is_quote'       => true,
            'number'         => '1323',
            'price'          => 100,
            'start'          => '2021-01-01',
            'end'            => '2024-01-01',
            'oem'            => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'key'  => 'key',
                'name' => 'oem1',
            ],
            'oem_said'       => '1234-5678-9012',
            'oemGroup'       => [
                'id'   => '52f2faec-5a80-4cdb-8cee-669b942ae1ef',
                'key'  => 'key',
                'name' => 'name',
            ],
            'type'           => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name aaa',
            ],
            'statuses'       => [],
            'customer'       => [
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
                        'types'       => [],
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
                'changed_at'      => '2021-10-19T10:15:00+00:00',
                'synced_at'       => '2021-10-19T10:25:00+00:00',
            ],
            'reseller'       => [
                'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                'name'            => 'reseller1',
                'customers_count' => 0,
                'locations_count' => 1,
                'assets_count'    => 0,
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
                        'types'       => [],
                    ],
                ],
            ],
            'currency'       => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                'name' => 'Currency1',
                'code' => 'CUR',
            ],
            'entries'        => [
                [
                    'id'               => '7294133d-33d4-4c0d-94bb-b012a5b0512c',
                    'service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                    'document_id'      => '323feb6f-dff3-4eb7-a2d6-b064139be2dd',
                    'net_price'        => 123.45,
                    'list_price'       => 67.00,
                    'discount'         => -8.00,
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
                    'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                    'serviceGroup'     => [
                        'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                        'name'   => 'Group',
                        'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'sku'    => 'SKU#123',
                    ],
                    'serviceLevel'     => [
                        'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                        'name'             => 'Level',
                        'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                        'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'sku'              => 'SKU#123',
                        'description'      => 'description',
                    ],
                    'asset_id'         => '8da0cf0e-2d59-4ab7-b5e5-6d3681e98429',
                    'asset'            => [
                        'id' => '8da0cf0e-2d59-4ab7-b5e5-6d3681e98429',
                    ],
                ],
            ],
            'language'       => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                'name' => 'Lang1',
                'code' => 'en',
            ],
            'contacts'       => [
                [
                    'name'        => 'contact2',
                    'email'       => 'contact2@test.com',
                    'phone_valid' => false,
                ],
            ],
            'distributor'    => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                'name' => 'distributor1',
            ],
            'assets_count'   => 1,
            'changed_at'     => '2021-10-19T10:15:00+00:00',
            'synced_at'      => '2021-10-19T10:25:00+00:00',
        ];
        $contract = [
            'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
            'oem_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
            'customer_id'    => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
            'type_id'        => 'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
            'reseller_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
            'currency_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
            'language_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
            'distributor_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
            'is_contract'    => true,
            'is_quote'       => false,
            'number'         => '1323',
            'price'          => 100,
            'start'          => '2021-01-01',
            'end'            => '2024-01-01',
            'oem'            => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'key'  => 'key',
                'name' => 'oem1',
            ],
            'oem_said'       => '1234-5678-9012',
            'oemGroup'       => [
                'id'   => '52f2faec-5a80-4cdb-8cee-669b942ae1ef',
                'key'  => 'key',
                'name' => 'name',
            ],
            'type'           => [
                'id'   => 'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                'name' => 'name bbb',
            ],
            'statuses'       => [],
            'customer'       => [
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
                        'types'       => [],
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
                'changed_at'      => '2021-10-19T10:15:00+00:00',
                'synced_at'       => '2021-10-19T10:25:00+00:00',
            ],
            'reseller'       => [
                'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                'name'            => 'reseller1',
                'customers_count' => 0,
                'locations_count' => 1,
                'assets_count'    => 0,
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
                        'types'       => [],
                    ],
                ],
            ],
            'currency'       => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                'name' => 'Currency1',
                'code' => 'CUR',
            ],
            'entries'        => [
                [
                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    'service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                    'document_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'net_price'        => 123.45,
                    'list_price'       => 67.00,
                    'discount'         => -8.00,
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
                    'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                    'serviceGroup'     => [
                        'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                        'name'   => 'Group',
                        'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'sku'    => 'SKU#123',
                    ],
                    'serviceLevel'     => [
                        'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                        'name'             => 'Level',
                        'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                        'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'sku'              => 'SKU#123',
                        'description'      => 'description',
                    ],
                    'asset_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                    'asset'            => [
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                    ],
                ],
            ],
            'language'       => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                'name' => 'Lang1',
                'code' => 'en',
            ],
            'contacts'       => [
                [
                    'name'        => 'contact2',
                    'email'       => 'contact2@test.com',
                    'phone_valid' => false,
                ],
            ],
            'distributor'    => [
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                'name' => 'distributor1',
            ],
            'assets_count'   => 1,
            'changed_at'     => '2021-10-19T10:15:00+00:00',
            'synced_at'      => '2021-10-19T10:25:00+00:00',
        ];

        return (new MergeDataProvider([
            'root'           => new CompositeDataProvider(
                new RootOrganizationDataProvider('documents'),
                new OrganizationUserDataProvider('documents', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('documents', null),
                        [
                            'ep.quote_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): Document {
                            $type     = Type::factory()->create([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ]);
                            $document = Document::factory()->create([
                                'type_id' => $type,
                            ]);

                            return $document;
                        },
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('documents', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrganizationUserDataProvider('documents', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'quote_types match'                        => [
                        new GraphQLPaginated('documents', self::class, [$quote], ['count' => 1]),
                        [
                            'ep.quote_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                    ],
                    'contract_types match'                     => [
                        new GraphQLPaginated('documents', self::class, [$quote, $contract], ['count' => 2]),
                        [
                            'ep.contract_types' => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                        ],
                        $factory,
                    ],
                    'quote_types + contract_types'             => [
                        new GraphQLPaginated('documents', self::class, [$quote, $contract], ['count' => 2]),
                        [
                            'ep.contract_types' => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                            'ep.quote_types'    => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                    ],
                    'quote_types and contract_types not match' => [
                        new GraphQLPaginated('documents', self::class, [], ['count' => 0]),
                        [
                            'ep.contract_types' => [
                                'fce6e662-e090-4451-ae0a-0916d1b3884c',
                            ],
                            'ep.quote_types'    => [
                                'b5ec2c89-a440-41d5-830d-14c0460f1cac',
                            ],
                        ],
                        $factory,
                    ],
                    'no quote_types + no contract_types'       => [
                        new GraphQLPaginated('documents', self::class, [], ['count' => 0]),
                        [
                            // empty
                        ],
                        $factory,
                    ],
                ]),
            ),
            'contracts-view' => new CompositeDataProvider(
                new OrganizationDataProvider('documents', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrganizationUserDataProvider('documents', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'quote_types match'                        => [
                        new GraphQLPaginated('documents', self::class, [], ['count' => 0]),
                        [
                            'ep.quote_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                    ],
                    'contract_types match'                     => [
                        new GraphQLPaginated('documents', self::class, [$contract], ['count' => 1]),
                        [
                            'ep.contract_types' => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                        ],
                        $factory,
                    ],
                    'quote_types + contract_types'             => [
                        new GraphQLPaginated('documents', self::class, [$contract], ['count' => 1]),
                        [
                            'ep.contract_types' => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                            'ep.quote_types'    => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                    ],
                    'quote_types and contract_types not match' => [
                        new GraphQLPaginated('documents', self::class, [], ['count' => 0]),
                        [
                            'ep.contract_types' => [
                                'fce6e662-e090-4451-ae0a-0916d1b3884c',
                            ],
                            'ep.quote_types'    => [
                                'b5ec2c89-a440-41d5-830d-14c0460f1cac',
                            ],
                        ],
                        $factory,
                    ],
                    'no quote_types + no contract_types'       => [
                        new GraphQLPaginated('documents', self::class, [], ['count' => 0]),
                        [
                            // empty
                        ],
                        $factory,
                    ],
                ]),
            ),
            'quotes-view'    => new CompositeDataProvider(
                new OrganizationDataProvider('documents', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrganizationUserDataProvider('documents', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'quote_types match'                        => [
                        new GraphQLPaginated('documents', self::class, [$quote], ['count' => 1]),
                        [
                            'ep.quote_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                    ],
                    'contract_types match'                     => [
                        new GraphQLPaginated('documents', self::class, [$quote], ['count' => 1]),
                        [
                            'ep.contract_types' => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                        ],
                        $factory,
                    ],
                    'quote_types + contract_types'             => [
                        new GraphQLPaginated('documents', self::class, [$quote], ['count' => 1]),
                        [
                            'ep.contract_types' => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                            'ep.quote_types'    => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                    ],
                    'quote_types and contract_types not match' => [
                        new GraphQLPaginated('documents', self::class, [], ['count' => 0]),
                        [
                            'ep.contract_types' => [
                                'fce6e662-e090-4451-ae0a-0916d1b3884c',
                            ],
                            'ep.quote_types'    => [
                                'b5ec2c89-a440-41d5-830d-14c0460f1cac',
                            ],
                        ],
                        $factory,
                    ],
                    'no quote_types + no contract_types'       => [
                        new GraphQLPaginated('documents', self::class, [], ['count' => 0]),
                        [
                            // empty
                        ],
                        $factory,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
