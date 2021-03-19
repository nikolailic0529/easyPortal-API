<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Type;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\UserDataProvider;
use Tests\DataProviders\TenantDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class AssetsTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this)->id;
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query assets($customer_id: Mixed!) {
                    assets(where:{ column: CUSTOMER, operator: EQ, value: $customer_id }) {
                        data {
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
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new UserDataProvider('assets'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLPaginated('assets', self::class, [
                        [
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
                            'type'          => [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
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
                                'contacts'        => [
                                    [
                                        'name'        => 'contact1',
                                        'email'       => 'contact1@test.com',
                                        'phone_valid' => false,
                                    ],
                                ],
                            ],
                        ],
                    ]),
                    static function (): Customer {
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
                            'oem_id' => $oem,
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
                                'locations_count' => 1,
                            ]);

                        // Type Creation belongs to
                        $type = Type::factory()->create([
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            'name' => 'name aaa',
                        ]);

                        Asset::factory()
                            ->for($oem)
                            ->for($product)
                            ->for($customer)
                            ->for($type)
                            ->for($location)
                            ->create([
                                'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                'serial_number' => '#PRODUCT_SERIAL_323',
                            ]);

                        return $customer;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
