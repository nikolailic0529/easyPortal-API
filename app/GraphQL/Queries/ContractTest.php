<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Document;
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
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class ContractTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $contractFactory = null,
    ): void {
        // Prepare
        $tenant = $this->setTenant($tenantFactory);
        $user   = $this->setUser($userFactory, $tenant);

        $contractId = 'wrong';

        if ($contractFactory) {
            $contract   = $contractFactory($this, $tenant, $user);
            $contractId = $contract->id;

            $this->setSettings([
                'ep.contract_types' => [$contract->type_id],
            ]);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query contract($id: ID!) {
                    contract(id: $id) {
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
                }
            ', ['id' => $contractId])
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
            'root'   => new CompositeDataProvider(
                new RootTenantDataProvider('contract'),
                new TenantUserDataProvider('contract'),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', null),
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'tenant' => new CompositeDataProvider(
                new TenantDataProvider('contract', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new UserDataProvider('contract'),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', self::class, [
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
                                    'net_price'   => '123.45',
                                    'list_price'  => '67.00',
                                    'discount'    => '-8.00',
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
                        ]),
                        static function (TestCase $test, Organization $organization): Document {
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
                            // Product creation belongs to
                            $product = Product::factory()->create([
                                'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                'name'   => 'Product1',
                                'oem_id' => $oem,
                                'sku'    => 'SKU#123',
                                'eol'    => '2022-12-30',
                                'eos'    => '2022-01-01',
                            ]);
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
                                    'id'              => $organization,
                                    'name'            => 'reseller1',
                                    'customers_count' => 0,
                                    'locations_count' => 1,
                                    'assets_count'    => 0,
                                ]);
                            // Currency creation belongs to
                            $currency = Currency::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'name' => 'Currency1',
                                'code' => 'CUR',
                            ]);

                            $customer->resellers()->attach($reseller);

                            return Document::factory()
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
                                    'net_price'  => '123.45',
                                    'list_price' => '67.00',
                                    'discount'   => '-8',
                                ])
                                ->create([
                                    'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'number' => '1323',
                                    'price'  => '100',
                                    'start'  => '2021-01-01',
                                    'end'    => '2024-01-01',
                                ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
