<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Quotes;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Currency;
use App\Models\Data\Language;
use App\Models\Data\Location;
use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Models\Data\ProductGroup;
use App\Models\Data\ProductLine;
use App\Models\Data\Psp;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Type;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\OemGroup;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLPaginated;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function count;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Quotes\Quotes
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class QuotesTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                              $orgFactory
     * @param UserFactory                                      $userFactory
     * @param SettingsFactory                                  $settingsFactory
     * @param Closure(static, ?Organization, ?User): void|null $quotesFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $quotesFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        if ($settingsFactory) {
            $this->setSettings($settingsFactory);
        }

        if ($quotesFactory) {
            $quotesFactory($this, $org, $user);
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                {
                    quotes {
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
                        oem_amp_id
                        oem_sar_number
                        oemGroup {
                            id
                            key
                            name
                        }
                        type {
                            id
                            name
                        }
                        statuses {
                            id
                            name
                        }
                        statuses_count
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
                            groups(groupBy: {asset_id: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {asset_id: asc}) {
                                count
                            }
                        }
                        entries {
                            id
                            document_id
                            service_level_id
                            list_price
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
                            monthly_list_price
                            monthly_retail_price
                            oem_said
                            oem_sar_number
                            environment_id
                            equipment_number
                            product_line_id
                            productLine {
                                id
                                key
                                name
                            }
                            product_group_id
                            productGroup {
                                id
                                key
                                name
                            }
                            asset_type_id
                            assetType {
                                id
                                key
                                name
                            }
                            language_id
                            language {
                                id
                                name
                                code
                            }
                            psp_id
                            psp {
                                id
                                key
                                name
                            }
                            removed_at
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
                    quotesAggregated {
                        count
                        groups(groupBy: {customer_id: asc}) {
                            key
                            count
                        }
                        groupsAggregated(groupBy: {customer_id: asc}) {
                            count
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
        $factory = static function (TestCase $test, Organization $org, User $user): void {
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
            $type = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name aaa',
            ]);

            // Location
            $location = Location::factory()->create([
                'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                'state'     => 'state1',
                'postcode'  => '19911',
                'line_one'  => 'line_one_data',
                'line_two'  => 'line_two_data',
                'latitude'  => '47.91634204',
                'longitude' => '-2.26318359',
            ]);

            // Reseller creation belongs to
            $reseller = Reseller::factory()->create([
                'id'              => $org,
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

            // Customer
            $customer = Customer::factory()
                ->hasContacts(1, [
                    'name'        => 'contact1',
                    'email'       => 'contact1@test.com',
                    'phone_valid' => false,
                ])
                ->create([
                    'id'              => $org,
                    'name'            => 'name aaa',
                    'assets_count'    => 0,
                    'locations_count' => 1,
                    'contacts_count'  => 1,
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
            $productLine  = ProductLine::factory()->create([
                'id'   => '6d2bb6c4-2b79-474b-9f7f-fbca859a2cf8',
                'key'  => 'Line#A',
                'name' => 'Line A',
            ]);
            $productGroup = ProductGroup::factory()->create([
                'id'   => 'e46a3ce7-2ff4-486a-bd77-3224cdaaa326',
                'key'  => 'Group#A',
                'name' => 'Group A',
            ]);
            $assetType    = Type::factory()->create([
                'id'   => '2213e78f-00bb-463a-b869-b9c52391bdf4',
                'key'  => 'Type#A',
                'name' => 'Type A',
            ]);
            $psp          = Psp::factory()->create([
                'id'   => '6e46c5d5-d6df-4fe8-905e-faf00147e0d1',
                'key'  => 'Psp#A',
                'name' => 'Psp A',
            ]);

            Document::factory()
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
                ->hasEntries(1, [
                    'id'                   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    'asset_id'             => Asset::factory()->ownedBy($org)->create([
                        'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                    ]),
                    'serial_number'        => null,
                    'product_id'           => $product,
                    'service_group_id'     => $serviceGroup,
                    'service_level_id'     => $serviceLevel,
                    'list_price'           => 67.00,
                    'renewal'              => 24.20,
                    'start'                => '2021-01-01',
                    'end'                  => '2024-01-01',
                    'monthly_list_price'   => 123.45,
                    'monthly_retail_price' => 543.21,
                    'oem_said'             => '1234-5678-9012',
                    'oem_sar_number'       => '1234567890',
                    'environment_id'       => '6d2bb6c4-2b79-474b-9f7f-fbca859a2cf8',
                    'equipment_number'     => '0987654321',
                    'product_line_id'      => $productLine,
                    'product_group_id'     => $productGroup,
                    'asset_type_id'        => $assetType,
                    'language_id'          => $language,
                    'psp_id'               => $psp,
                ])
                ->create([
                    'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'oem_said'       => '1234-5678-9012',
                    'oem_amp_id'     => 'AMP-ID-123',
                    'oem_sar_number' => 'SAR-123',
                    'number'         => '1323',
                    'price'          => 100,
                    'start'          => '2021-01-01',
                    'end'            => '2024-01-01',
                    'is_hidden'      => false,
                    'is_contract'    => false,
                    'is_quote'       => true,
                    'assets_count'   => 1,
                    'entries_count'  => 2,
                    'contacts_count' => 3,
                    'statuses_count' => 1,
                    'changed_at'     => '2021-10-19 10:15:00',
                    'synced_at'      => '2021-10-19 10:25:00',
                ]);

            // Hidden
            Document::factory()
                ->for($distributor)
                ->for($reseller)
                ->for($customer)
                ->for($type)
                ->create([
                    'is_hidden'   => true,
                    'is_contract' => false,
                    'is_quote'    => true,
                ]);

            // Contract
            Document::factory()
                ->for($distributor)
                ->for($reseller)
                ->for($customer)
                ->for($type)
                ->create([
                    'is_hidden'   => true,
                    'is_contract' => true,
                    'is_quote'    => false,
                ]);
        };
        $objects = [
            [
                'id'                => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                'oem_id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'customer_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
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
                'oem_amp_id'        => 'AMP-ID-123',
                'oem_sar_number'    => 'SAR-123',
                'oemGroup'          => [
                    'id'   => '52f2faec-5a80-4cdb-8cee-669b942ae1ef',
                    'key'  => 'key',
                    'name' => 'name',
                ],
                'type'              => [
                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                    'name' => 'name aaa',
                ],
                'statuses'          => [
                    [
                        'id'   => '126042b6-2bc7-4009-9366-b4c95a94c73b',
                        'name' => 'status a',
                    ],
                ],
                'statuses_count'    => 1,
                'customer'          => [
                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
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
                    'count'            => 1,
                    'groups'           => [
                        [
                            'key'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                            'count' => 1,
                        ],
                    ],
                    'groupsAggregated' => [
                        'count' => 1,
                    ],
                ],
                'entries'           => [
                    [
                        'id'                   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                        'service_level_id'     => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                        'document_id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                        'list_price'           => 67.00,
                        'renewal'              => 24.20,
                        'serial_number'        => null,
                        'product_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                        'product'              => [
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
                        'service_group_id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                        'serviceGroup'         => [
                            'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'name'   => 'Group',
                            'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'sku'    => 'SKU#123',
                        ],
                        'serviceLevel'         => [
                            'id'               => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                            'name'             => 'Level',
                            'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                            'oem_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'sku'              => 'SKU#123',
                            'description'      => 'description',
                        ],
                        'asset_id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                        'asset'                => [
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                        ],
                        'start'                => '2021-01-01',
                        'end'                  => '2024-01-01',
                        'monthly_list_price'   => 123.45,
                        'monthly_retail_price' => 543.21,
                        'oem_said'             => '1234-5678-9012',
                        'oem_sar_number'       => '1234567890',
                        'environment_id'       => '6d2bb6c4-2b79-474b-9f7f-fbca859a2cf8',
                        'equipment_number'     => '0987654321',
                        'product_line_id'      => '6d2bb6c4-2b79-474b-9f7f-fbca859a2cf8',
                        'productLine'          => [
                            'id'   => '6d2bb6c4-2b79-474b-9f7f-fbca859a2cf8',
                            'key'  => 'Line#A',
                            'name' => 'Line A',
                        ],
                        'product_group_id'     => 'e46a3ce7-2ff4-486a-bd77-3224cdaaa326',
                        'productGroup'         => [
                            'id'   => 'e46a3ce7-2ff4-486a-bd77-3224cdaaa326',
                            'key'  => 'Group#A',
                            'name' => 'Group A',
                        ],
                        'asset_type_id'        => '2213e78f-00bb-463a-b869-b9c52391bdf4',
                        'assetType'            => [
                            'id'   => '2213e78f-00bb-463a-b869-b9c52391bdf4',
                            'key'  => 'Type#A',
                            'name' => 'Type A',
                        ],
                        'language_id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                        'language'             => [
                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                            'name' => 'Lang1',
                            'code' => 'en',
                        ],
                        'psp_id'               => '6e46c5d5-d6df-4fe8-905e-faf00147e0d1',
                        'psp'                  => [
                            'id'   => '6e46c5d5-d6df-4fe8-905e-faf00147e0d1',
                            'key'  => 'Psp#A',
                            'name' => 'Psp A',
                        ],
                        'removed_at'           => null,
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
            ],
        ];

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('quotes'),
                new OrgUserDataProvider('quotes', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('quotes'),
                        [
                            // true
                        ],
                        static function (): Document {
                            return Document::factory()->create([
                                'is_hidden'   => false,
                                'is_contract' => false,
                                'is_quote'    => true,
                            ]);
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('quotes', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('quotes', [
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('quotes', $objects, [
                            'count'            => count($objects),
                            'groups'           => [
                                [
                                    'key'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                    'count' => count($objects),
                                ],
                            ],
                            'groupsAggregated' => [
                                'count' => 1,
                            ],
                        ]),
                        [
                            // empty
                        ],
                        $factory,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
