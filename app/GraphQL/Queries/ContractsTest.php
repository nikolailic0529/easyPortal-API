<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

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
 * @coversDefaultClass \App\GraphQL\Queries\Contracts
 */
class ContractsTest extends TestCase {
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
        Closure $contractsFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($settings) {
            $this->setSettings($settings);
        }

        if ($contractsFactory) {
            $contractsFactory($this, $organization, $user);
        }

        // Not empty?
        if ($expected instanceof GraphQLSuccess) {
            $this->assertGreaterThan(0, Document::query()->count());
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
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
                                name
                                oem_id
                                sku
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
        return (new MergeDataProvider([
            'root'           => new CompositeDataProvider(
                new RootOrganizationDataProvider('contracts'),
                new OrganizationUserDataProvider('contracts', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('contracts', null),
                        [
                            'ep.contract_types' => [
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
                new OrganizationDataProvider('contracts'),
                new OrganizationUserDataProvider('contracts', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('contracts', null),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): Document {
                            $type     = Type::factory()->create([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ]);
                            $reseller = Reseller::factory()->create([
                                'id' => $organization,
                            ]);
                            $customer = Customer::factory()->create();

                            $customer->resellers()->attach($reseller);

                            $document = Document::factory()->create([
                                'type_id'     => $type,
                                'reseller_id' => $reseller,
                                'customer_id' => $customer,
                            ]);

                            return $document;
                        },
                    ],
                ]),
            ),
            'organization'   => new CompositeDataProvider(
                new OrganizationDataProvider('contracts', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrganizationUserDataProvider('contracts', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLPaginated('contracts', self::class, [
                            [
                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                'customer_id'      => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'type_id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'reseller_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                'currency_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'language_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
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
                                'oem_said'         => '1234-5678-9012',
                                'oemGroup'         => [
                                    'id'   => '52f2faec-5a80-4cdb-8cee-669b942ae1ef',
                                    'key'  => 'key',
                                    'name' => 'name',
                                ],
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
                                ],
                                'reseller'         => [
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
                                'language'         => [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                                    'name' => 'Lang1',
                                    'code' => 'en',
                                ],
                                'contacts'         => [
                                    [
                                        'name'        => 'contact2',
                                        'email'       => 'contact2@test.com',
                                        'phone_valid' => false,
                                    ],
                                ],
                                'distributor'      => [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                                    'name' => 'distributor1',
                                ],
                                'assets_count'     => 1,
                            ],
                        ]),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization, User $user): void {
                            $user->save();
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
                            $type     = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
                            ]);
                            $location = Location::factory()->create([
                                'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'     => 'state1',
                                'postcode'  => '19911',
                                'line_one'  => 'line_one_data',
                                'line_two'  => 'line_two_data',
                                'latitude'  => '47.91634204',
                                'longitude' => '-2.26318359',
                            ]);
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

                            $customer->resellers()->attach($reseller);

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
                                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                        'reseller_id' => $reseller,
                                    ]),
                                    'serial_number'    => null,
                                    'product_id'       => $product,
                                    'service_group_id' => $serviceGroup,
                                    'service_level_id' => $serviceLevel,
                                    'net_price'        => 123,
                                    'list_price'       => 67.12,
                                    'discount'         => null,
                                    'renewal'          => 24.20,
                                ])
                                ->hasContacts(1, [
                                    'name'        => 'contact2',
                                    'email'       => 'contact2@test.com',
                                    'phone_valid' => false,
                                ])
                                ->create([
                                    'id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'oem_said'     => '1234-5678-9012',
                                    'number'       => '1323',
                                    'price'        => 100,
                                    'start'        => '2021-01-01',
                                    'end'          => '2024-01-01',
                                    'assets_count' => 1,
                                ]);

                            Document::factory()->create([
                                'type_id' => Type::factory()->create(),
                            ]);
                        },
                    ],
                    'no types'       => [
                        new GraphQLPaginated('contracts', self::class, [
                            // empty
                        ]),
                        [
                            'ep.contract_types' => [
                                // empty
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): void {
                            Document::factory()->create([
                                'reseller_id' => Reseller::factory()->create([
                                    'id' => $organization,
                                ]),
                            ]);
                        },
                    ],
                    'type not match' => [
                        new GraphQLPaginated('contracts', self::class, [
                            // empty
                        ]),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): void {
                            Document::factory()->create([
                                'reseller_id' => Reseller::factory()->create([
                                    'id' => $organization,
                                ]),
                            ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
