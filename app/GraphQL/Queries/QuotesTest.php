<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Oem;
use App\Models\Product;
use App\Models\Reseller;
use App\Models\Type;
use Closure;
use Illuminate\Contracts\Config\Repository;
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
class QuotesTest extends TestCase {
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $tenantFactory,
        Closure $userFactory = null,
        Closure $quotesFactory = null,
    ): void {
        // Prepare
        $this->setUser($userFactory, $this->setTenant($tenantFactory));

        if ($quotesFactory) {
            $typeId = $quotesFactory($this);
            $this->app->make(Repository::class)->set('easyportal.quotes_type_ids', [$typeId]);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
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
                                    lat
                                    lng
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
                            }
                            currency {
                                id
                                name
                                code
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
        return (new CompositeDataProvider(
            new TenantDataProvider(),
            new UserDataProvider('quotes'),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLPaginated('quotes', self::class, [
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
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                'name' => 'reseller1',
                            ],
                            'currency'    => [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'name' => 'Currency1',
                                'code' => 'CUR',
                            ],
                        ],
                    ]),
                    static function (): string {
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
                                'locations_count' => 1,
                                'contacts_count'  => 1,
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
                        $reseller = Reseller::factory()->create([
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                            'name' => 'reseller1',
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
                            ->create([
                                'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                'number' => '1323',
                                'price'  => '100',
                                'start'  => '2021-01-01',
                                'end'    => '2024-01-01',
                            ]);
                        return $type->id;
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
