<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Asset;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\DocumentEntryField;
use App\Models\Field;
use App\Models\Language;
use App\Models\Location;
use App\Models\Note;
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
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

use function json_encode;

/**
 * @internal
 * @coversNothing
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class QuoteTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $quoteFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $quoteId = 'wrong';

        if ($quoteFactory) {
            $quote   = $quoteFactory($this, $org, $user);
            $quoteId = $quote->id;

            $this->setSettings([
                'ep.document_statuses_no_price' => ['874e9e92-6328-4d44-ab70-4589029e3dad'],
                'ep.document_statuses_hidden'   => [],
                'ep.quote_types'                => [$quote->type_id],
            ]);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query quote($id: ID!) {
                    quote(id: $id) {
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
                        statuses_count
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
                            contacts_count
                            contacts {
                                name
                                email
                                phone_valid
                            }
                            changed_at
                            synced_at
                        }
                        currency {
                            id
                            name
                            code
                        }
                        entries_count
                        entriesAggregated {
                            count
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
                            start
                            end
                            fields {
                                field_id
                                value
                            }
                        }
                        language {
                            id
                            name
                            code
                        }
                        contacts_count
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
                }
            ', ['id' => $quoteId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryNotes
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testQueryNotes(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $quoteFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $quoteId = 'wrong';

        if ($quoteFactory) {
            $quote   = $quoteFactory($this, $org, $user);
            $quoteId = $quote->id;

            $this->setSettings([
                'ep.document_statuses_hidden' => [],
                'ep.quote_types'              => [$quote->type_id],
            ]);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query quote($id: ID!) {
                    quote(id: $id) {
                        notes {
                            id
                            note
                            pinned
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
                                size
                            }
                        }
                        notesAggregated {
                            count
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
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('quote'),
                new OrgUserDataProvider('quote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote'),
                        static function (TestCase $test, Organization $organization): Document {
                            return Document::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('quote', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('quote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok'                         => [
                        new GraphQLSuccess('quote', [
                            'id'                => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            'oem_id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'customer_id'       => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'type_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            'reseller_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                            'currency_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                            'language_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                            'distributor_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                            'is_contract'       => false,
                            'is_quote'          => true,
                            'number'            => '1323',
                            'price'             => 100,
                            'start'             => '2021-01-01',
                            'end'               => '2024-01-01',
                            'oem'               => [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'key'  => 'key',
                                'name' => 'oem1',
                            ],
                            'oem_said'          => '1234-5678-9012',
                            'oemGroup'          => [
                                'id'   => '52f2faec-5a80-4cdb-8cee-669b942ae1ef',
                                'key'  => 'key',
                                'name' => 'name',
                            ],
                            'type'              => [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
                            ],
                            'statuses_count'    => 1,
                            'statuses'          => [
                                [
                                    'id'   => '126042b6-2bc7-4009-9366-b4c95a94c73b',
                                    'name' => 'status a',
                                ],
                            ],
                            'customer'          => [
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
                            'reseller'          => [
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
                                'contacts_count'  => 0,
                                'contacts'        => [],
                                'changed_at'      => '2021-10-19T10:15:00+00:00',
                                'synced_at'       => '2021-10-19T10:25:00+00:00',
                            ],
                            'currency'          => [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'name' => 'Currency1',
                                'code' => 'CUR',
                            ],
                            'entries_count'     => 2,
                            'entriesAggregated' => [
                                'count' => 1,
                            ],
                            'entries'           => [
                                [
                                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                    'service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                    'document_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'net_price'        => 123.45,
                                    'list_price'       => null,
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
                                    'start'            => '2021-01-01',
                                    'end'              => '2024-01-01',
                                    'fields'           => [
                                        [
                                            'field_id' => '1a17f7ce-8460-41d9-9fff-870102b7a4b8',
                                            'value'    => null,
                                        ],
                                        [
                                            'field_id' => '7807f9fd-15f3-4f06-a038-74756ddced47',
                                            'value'    => 'value',
                                        ],
                                    ],
                                ],
                            ],
                            'language'          => [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                                'name' => 'Lang1',
                                'code' => 'en',
                            ],
                            'contacts_count'    => 3,
                            'contacts'          => [
                                [
                                    'name'        => 'contact2',
                                    'email'       => 'contact2@test.com',
                                    'phone_valid' => false,
                                ],
                            ],
                            'distributor'       => [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                                'name' => 'distributor1',
                            ],
                            'assets_count'      => 1,
                            'changed_at'        => '2021-10-19T10:15:00+00:00',
                            'synced_at'         => '2021-10-19T10:25:00+00:00',
                        ]),
                        static function (TestCase $test, Organization $organization, User $user): Document {
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
                                'contacts_count'  => 0,
                                'changed_at'      => '2021-10-19 10:15:00',
                                'synced_at'       => '2021-10-19 10:25:00',
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
                                    'assets_count'    => 2,
                                    'contacts_count'  => 1,
                                    'locations_count' => 1,
                                    'changed_at'      => '2021-10-19 10:15:00',
                                    'synced_at'       => '2021-10-19 10:25:00',
                                ]);

                            $customer->resellers()->attach($reseller, [
                                'assets_count' => 0,
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

                            $document = Document::factory()
                                ->for($oem)
                                ->for($oemGroup)
                                ->for($customer)
                                ->for($type)
                                ->for($reseller)
                                ->for($currency)
                                ->for($language)
                                ->for($distributor)
                                ->hasStatuses(1, [
                                    'id'   => '126042b6-2bc7-4009-9366-b4c95a94c73b',
                                    'name' => 'status a',
                                ])
                                ->hasContacts(1, [
                                    'name'        => 'contact2',
                                    'email'       => 'contact2@test.com',
                                    'phone_valid' => false,
                                ])
                                ->create([
                                    'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'oem_said'       => '1234-5678-9012',
                                    'number'         => '1323',
                                    'price'          => 100,
                                    'start'          => '2021-01-01',
                                    'end'            => '2024-01-01',
                                    'assets_count'   => 1,
                                    'entries_count'  => 2,
                                    'contacts_count' => 3,
                                    'statuses_count' => 1,
                                    'changed_at'     => '2021-10-19 10:15:00',
                                    'synced_at'      => '2021-10-19 10:25:00',
                                ]);

                            $fieldType = (new DocumentEntryField())->getMorphClass();
                            $fieldA    = Field::factory()->create([
                                'id'          => '7807f9fd-15f3-4f06-a038-74756ddced47',
                                'object_type' => $fieldType,
                            ]);
                            $fieldB    = Field::factory()->create([
                                'id'          => '1a17f7ce-8460-41d9-9fff-870102b7a4b8',
                                'object_type' => $fieldType,
                            ]);
                            $entry     = DocumentEntry::factory()->create([
                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'document_id'      => $document,
                                'asset_id'         => Asset::factory()->create([
                                    'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                    'reseller_id' => $reseller,
                                ]),
                                'serial_number'    => null,
                                'product_id'       => $product,
                                'service_group_id' => $serviceGroup,
                                'service_level_id' => $serviceLevel,
                                'net_price'        => 123.45,
                                'list_price'       => null,
                                'discount'         => -8,
                                'renewal'          => 24.20,
                                'start'            => '2021-01-01',
                                'end'              => '2024-01-01',
                            ]);

                            DocumentEntryField::factory()->create([
                                'id'                => $fieldA,
                                'document_entry_id' => $entry,
                                'document_id'       => $document,
                                'field_id'          => $fieldA,
                                'value'             => 'value',
                            ]);

                            DocumentEntryField::factory()->create([
                                'id'                => $fieldB,
                                'document_entry_id' => $entry,
                                'document_id'       => $document,
                                'field_id'          => $fieldB,
                                'value'             => null,
                            ]);

                            return $document;
                        },
                    ],
                    'hiding price'               => [
                        new GraphQLSuccess('quote', new JsonFragment('price', json_encode(null))),
                        static function (TestCase $test, Organization $organization): Document {
                            $type     = Type::factory()->create([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ]);
                            $reseller = Reseller::factory()->create([
                                'id' => $organization,
                            ]);

                            return Document::factory()
                                ->for($type)
                                ->for($reseller)
                                ->hasStatuses(1, [
                                    'id' => '874e9e92-6328-4d44-ab70-4589029e3dad',
                                ])
                                ->create([
                                    'price'       => 100,
                                    'customer_id' => null,
                                ]);
                        },
                    ],
                    'entries: hiding list_price' => [
                        new GraphQLSuccess(
                            'quote',
                            new JsonFragment('entries.0.list_price', json_encode(null)),
                        ),
                        static function (TestCase $test, Organization $organization): Document {
                            $type     = Type::factory()->create([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ]);
                            $reseller = Reseller::factory()->create([
                                'id' => $organization,
                            ]);
                            $document = Document::factory()
                                ->for($type)
                                ->for($reseller)
                                ->hasStatuses(1, [
                                    'id' => '874e9e92-6328-4d44-ab70-4589029e3dad',
                                ])
                                ->create([
                                    'customer_id' => null,
                                ]);

                            DocumentEntry::factory()->create([
                                'document_id' => $document,
                                'list_price'  => 100,
                                'net_price'   => 100,
                            ]);

                            return $document;
                        },
                    ],
                    'entries: hiding net_price'  => [
                        new GraphQLSuccess(
                            'quote',
                            new JsonFragment('entries.0.net_price', json_encode(null)),
                        ),
                        static function (TestCase $test, Organization $organization): Document {
                            $type     = Type::factory()->create([
                                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ]);
                            $reseller = Reseller::factory()->create([
                                'id' => $organization,
                            ]);
                            $document = Document::factory()
                                ->for($type)
                                ->for($reseller)
                                ->hasStatuses(1, [
                                    'id' => '874e9e92-6328-4d44-ab70-4589029e3dad',
                                ])
                                ->create([
                                    'customer_id' => null,
                                ]);

                            DocumentEntry::factory()->create([
                                'document_id' => $document,
                                'list_price'  => 100,
                                'net_price'   => 100,
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
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('quote'),
                new OrgUserDataProvider('quote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote'),
                        static function (TestCase $test, Organization $organization): Document {
                            return Document::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('quote', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('quote', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('quote', [
                            'notes'           => [
                                [
                                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                    'note'       => 'Note',
                                    'pinned'     => true,
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
                                            'size' => 100,
                                            'url'  => $url,
                                        ],
                                    ],
                                ],
                            ],
                            'notesAggregated' => [
                                'count' => 1,
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization, User $user): Document {
                            // Type Creation belongs to
                            $type = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
                            ]);
                            // Reseller creation belongs to
                            $reseller  = Reseller::factory()
                                ->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                ]);
                            $reseller2 = Reseller::factory()->create();
                            $document  = Document::factory()
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
                                    'size' => 100,
                                    'path' => 'http://example.com/document.csv',
                                ])
                                ->create([
                                    'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                    'note'       => 'Note',
                                    'pinned'     => true,
                                    'created_at' => '2021-07-11T23:27:47+00:00',
                                    'updated_at' => '2021-07-11T23:27:47+00:00',
                                ]);
                            // same org different document
                            Document::factory()
                                ->for($reseller)
                                ->hasNotes(1)
                                ->create();
                            // different org
                            Document::factory()
                                ->for($reseller2)
                                ->hasNotes(1)
                                ->create();

                            return $document;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
