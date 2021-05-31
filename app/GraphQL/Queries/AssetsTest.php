<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\AssetCoverage;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Reseller;
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
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
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
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this, $organization, $user)->id;
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query assets($customer_id: ID!) {
                    assets(where:{ customer_id: { eq: $customer_id } }) {
                        data {
                            id
                            oem_id
                            product_id
                            type_id
                            customer_id
                            location_id
                            serial_number
                            contacts_count
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
                                support {
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
                            status {
                                id
                                name
                            }
                            contacts {
                                name
                                email
                                phone_valid
                            }
                            coverage_id
                            coverage {
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
                    'view-assets',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('assets', null),
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'view-customers' => new CompositeDataProvider(
                new OrganizationDataProvider('assets'),
                new UserDataProvider('assets', [
                    'view-customers',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('assets', null),
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization'   => new CompositeDataProvider(
                new OrganizationDataProvider('assets', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
                new UserDataProvider('assets', [
                    'view-assets',
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
                                'coverage_id'    => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20948',
                                'serial_number'  => '#PRODUCT_SERIAL_323',
                                'contacts_count' => 1,
                                'oem'            => [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'abbr' => 'abbr',
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
                                        'abbr' => 'abbr',
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
                                'warranties'     => [
                                    [
                                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                        'reseller_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                        'customer_id' => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                        'document_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                        'start'       => '2021-01-01',
                                        'end'         => '2022-01-01',
                                        'note'        => 'note',
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
                                        'support'     => [
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
                                                    'latitude'  => 49.91634204,
                                                    'longitude' => 90.26318359,
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
                                'coverage'       => [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20948',
                                    'name' => 'COVERED_ON_CONTRACT',
                                ],
                                'tags'           => [
                                    [
                                        'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20950',
                                        'name' => 'Software',
                                    ],
                                ],
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization): Customer {
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
                                'oem_id' => $oem,
                                'sku'    => 'SKU#123',
                                'eol'    => '2022-12-30',
                                'eos'    => '2022-01-01',
                            ]);
                            // Customer Creation creation belongs to
                            $customer = Customer::factory()
                                ->hasLocations(1, [
                                    'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                    'state'     => 'state1',
                                    'postcode'  => '19911',
                                    'line_one'  => 'line_one_data',
                                    'line_two'  => 'line_two_data',
                                    'latitude'  => '47.91634204',
                                    'longitude' => '-2.26318359',
                                ])
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

                            // Type Creation belongs to
                            $type = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
                            ]);
                            // Product creation for support
                            $product2 = Product::factory()->create([
                                'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24998',
                                'name'   => 'Product2',
                                'oem_id' => $oem,
                                'sku'    => 'SKU#321',
                                'eol'    => '2022-12-30',
                                'eos'    => '2022-01-01',
                            ]);
                            // Document creation for support
                            $document = Document::factory()->create([
                                'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                'support_id' => $product2,
                            ]);
                            // Document entry creation for services
                            DocumentEntry::factory()->create([
                                'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'document_id' => $document,
                                'asset_id'    => Asset::factory()->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                ]),
                                'product_id'  => $product,
                                'service_id'  => $product,
                            ]);
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
                            // Status belongs to
                            $status = Status::factory()->create([
                                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                'name'        => 'active',
                                'key'         => 'active',
                                'object_type' => (new Asset())->getMorphClass(),
                            ]);
                            // Coverages belongs to
                            $coverage = AssetCoverage::factory()->create([
                                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20948',
                                'name' => 'COVERED_ON_CONTRACT',
                            ]);
                            // Asset Creation
                            $asset = Asset::factory()
                                ->for($oem)
                                ->for($product)
                                ->for($customer)
                                ->for($type)
                                ->for($location)
                                ->for($status)
                                ->for($coverage, 'coverage')
                                ->hasTags(1, [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20950',
                                    'name' => 'Software',
                                ])
                                ->hasContacts(1, [
                                    'name'        => 'contact2',
                                    'email'       => 'contact2@test.com',
                                    'phone_valid' => false,
                                ])
                                ->create([
                                    'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'reseller_id'    => $reseller,
                                    'serial_number'  => '#PRODUCT_SERIAL_323',
                                    'contacts_count' => 1,
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

                            $customer->resellers()->attach($reseller);

                            return $customer;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
