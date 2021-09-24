<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Models\ServiceGroup;
use App\Models\ServiceLevel;
use App\Models\Status;
use App\Models\Type;
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
class AssetsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param array<string,mixed> $settings
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
                query assets($customer_id: ID!) {
                    assets(where:{ customer_id: { equal: $customer_id } }) {
                        data {
                            id
                            oem_id
                            product_id
                            type_id
                            customer_id
                            location_id
                            serial_number
                            contacts_count
                            data_quality
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
                            customer {
                                id
                                name
                                assets_count
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
                                contacts_count
                                contacts {
                                    name
                                    email
                                    phone_valid
                                }
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
                            }
                            status {
                                id
                                name
                            }
                            contacts {
                                name
                                email
                                phone_valid
                            }
                            coverages {
                                id
                                name
                            }
                            tags {
                                id
                                name
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
                }
            ', ['customer_id' => $customerId])
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
                new RootOrganizationDataProvider('assets'),
                new OrganizationUserDataProvider('assets', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('assets', null),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('assets'),
                new OrganizationUserDataProvider('assets', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('assets', null),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization'   => new CompositeDataProvider(
                new OrganizationDataProvider('assets', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
                new OrganizationUserDataProvider('assets', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('assets', self::class, [
                            [
                                'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                'oem_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'product_id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                'location_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                'type_id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'customer_id'    => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                'serial_number'  => '#PRODUCT_SERIAL_323',
                                'contacts_count' => 1,
                                'data_quality'   => '130',
                                'oem'            => [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'key'  => 'key',
                                    'name' => 'oem1',
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
                                'type'           => [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    'name' => 'name aaa',
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
                                        'customer'      => [
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
                                        'reseller'      => [
                                            'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
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
                                                [
                                                    'location_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                                    'location'    => [
                                                        'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
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
                                    ],
                                ],
                                'status'         => [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                    'name' => 'active',
                                ],
                                'contacts'       => [
                                    [
                                        'name'        => 'contact2',
                                        'email'       => 'contact2@test.com',
                                        'phone_valid' => false,
                                    ],
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
                        ]),
                        [
                            'ep.contract_types' => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): Customer {
                            // OEM Creation belongs to
                            $oem = Oem::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'key'  => 'key',
                                'name' => 'oem1',
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

                            CustomerLocation::factory()->create([
                                'customer_id' => $customer,
                                'location_id' => $location,
                            ]);

                            // Location belongs to
                            $assetLocation = Location::factory()->create([
                                'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                'state'     => 'state1',
                                'postcode'  => '19911',
                                'line_one'  => 'line_one_data',
                                'line_two'  => 'line_two_data',
                                'latitude'  => '47.91634204',
                                'longitude' => '-2.26318359',
                            ]);

                            ResellerLocation::factory()->create([
                                'reseller_id' => $reseller,
                                'location_id' => $assetLocation,
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
                            $document     = Document::factory()->create([
                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                'type_id'          => $documentType,
                                'service_group_id' => $serviceGroup,
                                'reseller_id'      => $reseller,
                            ]);
                            // Document entry creation for services
                            DocumentEntry::factory()->create([
                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'document_id'      => $document,
                                'asset_id'         => Asset::factory()->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                ]),
                                'product_id'       => $product,
                                'service_level_id' => $serviceLevel,
                            ]);
                            // Status belongs to
                            $status = Status::factory()->create([
                                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                'name'        => 'active',
                                'key'         => 'active',
                                'object_type' => (new Asset())->getMorphClass(),
                            ]);
                            // Asset Creation
                            $asset = Asset::factory()
                                ->for($oem)
                                ->for($product)
                                ->for($customer)
                                ->for($type)
                                ->for($assetLocation)
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
                                    'reseller_id'    => $reseller,
                                    'serial_number'  => '#PRODUCT_SERIAL_323',
                                    'contacts_count' => 1,
                                    'data_quality'   => '130',
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
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
