<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Asset;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Language;
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
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Quotes
 */
class QuotesTest extends TestCase {
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
        Closure $quotesFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($settings) {
            $this->setSettings($settings);
        }

        if ($quotesFactory) {
            $quotesFactory($this, $organization, $user);
        }

        // Not empty?
        if ($expected instanceof GraphQLSuccess) {
            $this->assertGreaterThan(0, Document::query()->count());
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    quotes {
                        data {
                            id
                            oem_id
                            support_id
                            type_id
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
                                abbr
                                name
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
                                service_id
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
                                        abbr
                                        name
                                    }
                                }
                                service {
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
        $factory = static function (TestCase $test, Organization $organization): void {
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
            // Language creation belongs to
            $language = Language::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                'name' => 'Lang1',
                'code' => 'en',
            ]);
            // Distributor
            $distributor = Distributor::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                'name' => 'distributor1',
            ]);
            Document::factory()
                ->for($oem)
                ->for($product, 'support')
                ->for($customer)
                ->for($type)
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
                    'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    'asset_id'      => Asset::factory()->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                    ]),
                    'serial_number' => null,
                    'product_id'    => $product,
                    'service_id'    => $product,
                    'net_price'     => 123.45,
                    'list_price'    => 67.00,
                    'discount'      => -8,
                    'renewal'       => 24.20,
                ])
                ->create([
                    'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'number' => '1323',
                    'price'  => 100,
                    'start'  => '2021-01-01',
                    'end'    => '2024-01-01',
                ]);

            Document::factory()->create([
                'type_id' => Type::factory()->create([
                    'id' => 'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                ]),
            ]);

            $customer->resellers()->attach($reseller);
        };
        $objects = [
            [
                'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                'oem_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'support_id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                'customer_id'    => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                'type_id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'reseller_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                'currency_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                'language_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                'distributor_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                'number'         => '1323',
                'price'          => 100,
                'start'          => '2021-01-01',
                'end'            => '2024-01-01',
                'oem'            => [
                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    'abbr' => 'abbr',
                    'name' => 'oem1',
                ],
                'support'        => [
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
                'reseller'       => [
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
                'currency'       => [
                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'name' => 'Currency1',
                    'code' => 'CUR',
                ],
                'entries'        => [
                    [
                        'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                        'service_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                        'document_id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                        'net_price'     => 123.45,
                        'list_price'    => 67.00,
                        'discount'      => -8.00,
                        'renewal'       => 24.20,
                        'serial_number' => null,
                        'product_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
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
                        'service'       => [
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
            ],
        ];

        return (new MergeDataProvider([
            'root'           => new CompositeDataProvider(
                new RootOrganizationDataProvider('quotes'),
                new OrganizationUserDataProvider('quotes', [
                    'view-quotes',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('quotes', null),
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
            'view-customers' => new CompositeDataProvider(
                new OrganizationDataProvider('quotes'),
                new UserDataProvider('quotes', [
                    'view-customers',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('quotes', null),
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
                new OrganizationDataProvider('quotes', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new UserDataProvider('quotes', [
                    'view-quotes',
                ]),
                new ArrayDataProvider([
                    'quote_types match'                         => [
                        new GraphQLPaginated('quotes', self::class, $objects),
                        [
                            'ep.quote_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $factory,
                    ],
                    'no quote_types + contract_types not match' => [
                        new GraphQLPaginated('quotes', self::class, $objects),
                        [
                            'ep.contract_types' => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                        ],
                        $factory,
                    ],
                    'no quote_types + contract_types match'     => [
                        new GraphQLPaginated(
                            'quotes',
                            self::class,
                            new JsonFragment('0.id', '"2bf6d64b-df97-401c-9abd-dc2dd747e2b0"'),
                        ),
                        [
                            'ep.contract_types' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): void {
                            $customer = Customer::factory()->create();
                            $reseller = Reseller::factory()->create([
                                'id' => $organization,
                            ]);

                            $customer->resellers()->attach($reseller);

                            Document::factory()->create([
                                'id'          => '2bf6d64b-df97-401c-9abd-dc2dd747e2b0',
                                'customer_id' => $customer,
                                'reseller_id' => $reseller,
                            ]);
                        },
                    ],
                    'quote_types not match'                     => [
                        new GraphQLPaginated('quotes', self::class, [
                            // empty
                        ]),
                        [
                            'ep.quote_types' => [
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
                    'no quote_types + no contract_types'        => [
                        new GraphQLPaginated('quotes', self::class, [
                            // empty
                        ]),
                        [
                            'ep.quote_types' => [
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
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
