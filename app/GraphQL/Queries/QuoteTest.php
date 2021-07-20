<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Types\Note as NoteType;
use App\Models\Asset;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\Language;
use App\Models\Note;
use App\Models\Oem;
use App\Models\OemGroup;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Reseller;
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
use Tests\TestCase;

/**
 * @internal
 * @coversNothing
 */
class QuoteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     */
    public function testQuery(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $quoteFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $quoteId = 'wrong';

        if ($quoteFactory) {
            $quote   = $quoteFactory($this, $organization, $user);
            $quoteId = $quote->id;

            $this->setSettings([
                'ep.quote_types' => [$quote->type_id],
            ]);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query quote($id: ID!) {
                    quote(id: $id) {
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
                        oem_said
                        oemGroup {
                            id
                            key
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
                        assets_count
                    }
                }
            ', ['id' => $quoteId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryNotes
     */
    public function testQueryNotes(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $quoteFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $quoteId = 'wrong';

        if ($quoteFactory) {
            $quote   = $quoteFactory($this, $organization, $user);
            $quoteId = $quote->id;

            $this->setSettings([
                'ep.quote_types' => [$quote->type_id],
            ]);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query quote($id: ID!) {
                    quote(id: $id) {
                        notes {
                            data{
                                id
                                note
                                created_at
                                updated_at
                                user_id
                                user {
                                    id
                                    family_name
                                    given_name
                                }
                                files {
                                    id
                                    name
                                    url
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
            ', ['id' => $quoteId])
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
                new RootOrganizationDataProvider('quote'),
                new OrganizationUserDataProvider('quote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote', null),
                        static function (TestCase $test, Organization $organization): Document {
                            return Document::factory()->create();
                        },
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('quote'),
                new UserDataProvider('quote', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote', null),
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
                new OrganizationDataProvider('quote', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new UserDataProvider('quote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote', self::class, [
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
                            'oem_said'       => '1234-5678-9012',
                            'oemGroup'       => [
                                'id'   => '52f2faec-5a80-4cdb-8cee-669b942ae1ef',
                                'key'  => 'key',
                                'name' => 'name',
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
                                        'latitude'  => 49.91634204,
                                        'longitude' => 90.26318359,
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
                                    'list_price'    => null,
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
                            'assets_count'   => 1,
                        ]),
                        static function (TestCase $test, Organization $organization, User $user): Document {
                            // OEM Creation belongs to
                            $oem      = Oem::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'abbr' => 'abbr',
                                'name' => 'oem1',
                            ]);
                            $oemGroup = OemGroup::factory()->create([
                                'id'   => '52f2faec-5a80-4cdb-8cee-669b942ae1ef',
                                'key'  => 'key',
                                'name' => 'name',
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
                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
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

                            $customer->resellers()->attach($reseller);
                            // Distributor
                            $distributor = Distributor::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                                'name' => 'distributor1',
                            ]);
                            $document    = Document::factory()
                                ->for($oem)
                                ->for($oemGroup)
                                ->for($product, 'support')
                                ->for($customer)
                                ->for($type)
                                ->for($reseller)
                                ->for($currency)
                                ->for($language)
                                ->for($distributor)
                                ->hasEntries(1, [
                                    'id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                    'asset_id'      => Asset::factory()->create([
                                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                    ]),
                                    'serial_number' => null,
                                    'product_id'    => $product,
                                    'service_id'    => $product,
                                    'net_price'     => 123.45,
                                    'list_price'    => null,
                                    'discount'      => -8,
                                    'renewal'       => 24.20,
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
                            return $document;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryNotes(): array {
        $url = 'https://example.com/files/f9834bc1-2f2f-4c57-bb8d-7a224ac2E988';

        return (new MergeDataProvider([
            'root'           => new CompositeDataProvider(
                new RootOrganizationDataProvider('quote'),
                new OrganizationUserDataProvider('quote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote', null),
                        static function (TestCase $test, Organization $organization): Document {
                            return Document::factory()->create();
                        },
                    ],
                ]),
            ),
            'customers-view' => new CompositeDataProvider(
                new OrganizationDataProvider('quote'),
                new UserDataProvider('quote', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote', null),
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
                new OrganizationDataProvider('quote', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new UserDataProvider('quote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote', new JsonFragmentPaginatedSchema('notes', NoteType::class), [
                            'notes' => [
                                'data'          => [
                                    [
                                        'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                        'note'       => 'Note',
                                        'created_at' => '2021-07-11T23:27:47+00:00',
                                        'updated_at' => '2021-07-11T23:27:47+00:00',
                                        'user_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E999',
                                        'user'       => [
                                            'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E999',
                                            'given_name'  => 'first',
                                            'family_name' => 'last',
                                        ],
                                        'files'      => [
                                            [
                                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E988',
                                                'name' => 'document',
                                                'url'  => $url,
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
                        static function (TestCase $test, Organization $organization, User $user): Document {
                            // Type Creation belongs to
                            $type = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
                            ]);
                            // Reseller creation belongs to
                            $reseller = Reseller::factory()
                                ->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',

                                ]);
                            $document = Document::factory()
                                ->for($type)
                                ->for($reseller)
                                ->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                ]);
                            // Note
                            Note::factory()
                                ->forUser([
                                    'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E999',
                                    'given_name'  => 'first',
                                    'family_name' => 'last',
                                ])
                                ->for($document)
                                ->hasFiles(1, [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E988',
                                    'name' => 'document',
                                    'path' => 'http://example.com/document.csv',
                                ])
                                ->create([
                                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                    'note'       => 'Note',
                                    'created_at' => '2021-07-11T23:27:47+00:00',
                                    'updated_at' => '2021-07-11T23:27:47+00:00',
                                ]);
                            return $document;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
