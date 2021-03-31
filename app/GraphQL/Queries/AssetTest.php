<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\UserDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class AssetTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $assetsFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        $assetId = 'wrong';

        if ($assetsFactory) {
            $assetId = $assetsFactory($this)->id;
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query asset($id: ID!) {
                    asset(id: $id) {
                        id
                        oem_id
                        product_id
                        type_id
                        customer_id
                        location_id
                        serial_number
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
                                lat
                                lng
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
                            lat
                            lng
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
                                    lat
                                    lng
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
                                    lat
                                    lng
                                }
                            }
                        }
                    }
                }
            ', ['id' => $assetId])
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new UserDataProvider('asset'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('asset', self::class, [
                        'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                        'oem_id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'product_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                        'location_id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                        'type_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        'customer_id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        'serial_number' => '#PRODUCT_SERIAL_323',
                        'oem'           => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'abbr' => 'abbr',
                            'name' => 'oem1',
                        ],
                        'type'          => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
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
                            'id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                            'state'    => 'state1',
                            'postcode' => '19911',
                            'line_one' => 'line_one_data',
                            'line_two' => 'line_two_data',
                            'lat'      => '47.91634204',
                            'lng'      => '-2.26318359',
                        ],
                        'customer'      => [
                            'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'            => 'name aaa',
                            'assets_count'    => 0,
                            'locations_count' => 1,
                            'locations'       => [
                                [
                                    'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                    'state'    => 'state1',
                                    'postcode' => '19911',
                                    'line_one' => 'line_one_data',
                                    'line_two' => 'line_two_data',
                                    'lat'      => '47.91634204',
                                    'lng'      => '-2.26318359',
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
                                'customer'    => [
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 0,
                                    'locations_count' => 1,
                                    'locations'       => [
                                        [
                                            'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                            'state'    => 'state1',
                                            'postcode' => '19911',
                                            'line_one' => 'line_one_data',
                                            'line_two' => 'line_two_data',
                                            'lat'      => '47.91634204',
                                            'lng'      => '-2.26318359',
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
                                            'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20954',
                                            'state'    => 'state2',
                                            'postcode' => '19912',
                                            'line_one' => 'reseller_one_data',
                                            'line_two' => 'reseller_two_data',
                                            'lat'      => '49.91634204',
                                            'lng'      => '90.26318359',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    static function (): Asset {
                        // OEM Creation belongs to
                        $oem = Oem::factory()->create([
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'abbr' => 'abbr',
                            'name' => 'oem1',
                        ]);
                        // Location belongs to
                        $location = Location::factory()->create([
                            'id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                            'state'    => 'state1',
                            'postcode' => '19911',
                            'line_one' => 'line_one_data',
                            'line_two' => 'line_two_data',
                            'lat'      => '47.91634204',
                            'lng'      => '-2.26318359',
                        ]);
                        // Product creation belongs to
                        $product = Product::factory()->create([
                            'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                            'name'   => 'Product1',
                            'oem_id' => $oem->id,
                            'sku'    => 'SKU#123',
                            'eol'    => '2022-12-30',
                            'eos'    => '2022-01-01',
                        ]);
                        // Customer Creation creation belongs to
                        $customer = Customer::factory()
                            ->hasLocations(1, [
                                'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'    => 'state1',
                                'postcode' => '19911',
                                'line_one' => 'line_one_data',
                                'line_two' => 'line_two_data',
                                'lat'      => '47.91634204',
                                'lng'      => '-2.26318359',
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
                        // Product creation for package
                        $product2 = Product::factory()->create([
                            'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24998',
                            'name'   => 'Product2',
                            'oem_id' => $oem->id,
                            'sku'    => 'SKU#321',
                            'eol'    => '2022-12-30',
                            'eos'    => '2022-01-01',
                        ]);
                        // Document creation for package
                        $document = Document::factory()->create([
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
                        $reseller = Reseller::factory()
                            ->hasLocations(1, [
                                'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20954',
                                'state'    => 'state2',
                                'postcode' => '19912',
                                'line_one' => 'reseller_one_data',
                                'line_two' => 'reseller_two_data',
                                'lat'      => '49.91634204',
                                'lng'      => '90.26318359',
                            ])
                            ->create([
                                'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 1,
                                'assets_count'    => 0,
                            ]);
                        $asset    = Asset::factory()
                            ->for($oem)
                            ->for($product)
                            ->for($customer)
                            ->for($type)
                            ->for($location)
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

                        return $asset;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
