<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Contracts;

use App\Models\Asset;
use App\Models\ChangeRequest;
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
use App\Models\DocumentEntry;
use App\Models\Note;
use App\Models\OemGroup;
use App\Models\Organization;
use App\Models\QuoteRequest;
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
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithUser;

/**
 * @internal
 * @coversNothing
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class ContractTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param Closure(static, ?Organization, ?User): Document|null $contractFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $contractFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $key  = $contractFactory
            ? $contractFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query contract($id: ID!) {
                    contract(id: $id) {
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
                            groups(groupBy: {start: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {start: asc}) {
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
                }
            ', ['id' => $key])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryNotes
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param Closure(static, ?Organization, ?User): Document|null $contractFactory
     */
    public function testQueryNotes(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $contractFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $key  = $contractFactory
            ? $contractFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query contract($id: ID!) {
                    contract(id: $id) {
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
                            quote_request_id
                            quoteRequest {
                               id
                            }
                            change_request_id
                            changeRequest {
                                id
                            }
                        }
                        notesAggregated {
                            count
                            groups(groupBy: {user_id: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {user_id: asc}) {
                                count
                            }
                        }
                    }
                }
            ', ['id' => $key])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryChangeRequests
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param Closure(static, ?Organization, ?User): Document|null $contractFactory
     */
    public function testQueryChangeRequests(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $contractFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $key  = $contractFactory
            ? $contractFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query contract($id: ID!) {
                    contract(id: $id) {
                        changeRequests {
                            id
                            subject
                            message
                            from
                            to
                            cc
                            bcc
                            user_id
                            files {
                                name
                            }
                        }
                    }
                }
            ', ['id' => $key])
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
                new OrgRootDataProvider('contract'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract'),
                        static function (TestCase $test, Organization $organization): Document {
                            return Document::factory()->create([
                                'is_hidden'   => false,
                                'is_contract' => true,
                                'is_quote'    => false,
                            ]);
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('contract', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', [
                            'id'                => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            'oem_id'            => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'customer_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                            'type_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            'reseller_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                            'currency_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                            'language_id'       => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                            'distributor_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                            'is_contract'       => true,
                            'is_quote'          => false,
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
                                'assets_count'    => 1,
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
                                'assets_count'    => 1,
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
                                        'count' => 1,
                                        'key'   => '2021-01-01',
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
                        ]),
                        static function (TestCase $test, Organization $org, User $user): Document {
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
                            // Customer Creation creation belongs to
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
                                'id'              => $org,
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 1,
                                'assets_count'    => 1,
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
                                    'id'              => $org,
                                    'name'            => 'name aaa',
                                    'assets_count'    => 1,
                                    'contacts_count'  => 1,
                                    'locations_count' => 1,
                                    'changed_at'      => '2021-10-19 10:15:00',
                                    'synced_at'       => '2021-10-19 10:25:00',
                                ]);

                            $customer->resellers()->attach($reseller, [
                                'assets_count' => 1,
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
                                    'oem_amp_id'     => 'AMP-ID-123',
                                    'oem_sar_number' => 'SAR-123',
                                    'number'         => '1323',
                                    'price'          => '100',
                                    'start'          => '2021-01-01',
                                    'end'            => '2024-01-01',
                                    'is_hidden'      => false,
                                    'is_contract'    => true,
                                    'is_quote'       => false,
                                    'assets_count'   => 1,
                                    'entries_count'  => 2,
                                    'contacts_count' => 3,
                                    'statuses_count' => 1,
                                    'changed_at'     => '2021-10-19 10:15:00',
                                    'synced_at'      => '2021-10-19 10:25:00',
                                ]);

                            DocumentEntry::factory()->create([
                                'id'                   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'document_id'          => $document,
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
                new OrgRootDataProvider('contract'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract'),
                        static function (TestCase $test, Organization $organization): Document {
                            return Document::factory()->create([
                                'is_hidden'   => false,
                                'is_contract' => true,
                                'is_quote'    => false,
                            ]);
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('contract', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', [
                            'notes'           => [
                                [
                                    'id'                => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                    'note'              => 'Note',
                                    'pinned'            => true,
                                    'created_at'        => '2021-07-11T23:27:47+00:00',
                                    'updated_at'        => '2021-07-11T23:27:47+00:00',
                                    'user_id'           => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E999',
                                    'user'              => [
                                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E999',
                                        'given_name'  => 'first',
                                        'family_name' => 'last',
                                    ],
                                    'files'             => [
                                        [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E988',
                                            'name' => 'contract',
                                            'url'  => $url,
                                            'size' => 100,
                                        ],
                                    ],
                                    'quote_request_id'  => '50db1187-22f7-473e-8fe4-abbc7a2fa1f2',
                                    'quoteRequest'      => [
                                        'id' => '50db1187-22f7-473e-8fe4-abbc7a2fa1f2',
                                    ],
                                    'change_request_id' => '12b9075f-b0c5-4ee0-bdad-6c6db5ba1b67',
                                    'changeRequest'     => [
                                        'id' => '12b9075f-b0c5-4ee0-bdad-6c6db5ba1b67',
                                    ],
                                ],
                            ],
                            'notesAggregated' => [
                                'count'            => 1,
                                'groups'           => [
                                    [
                                        'count' => 1,
                                        'key'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E999',
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 1,
                                ],
                            ],
                        ]),
                        static function (TestCase $test, Organization $org, User $user): Document {
                            // Type Creation belongs to
                            $type         = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
                            ]);
                            $organization = Organization::factory()->create();
                            $document     = Document::factory()
                                ->ownedBy($org)
                                ->for($type)
                                ->create([
                                    'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'is_hidden'   => false,
                                    'is_contract' => true,
                                    'is_quote'    => false,
                                ]);
                            // Note
                            Note::factory()
                                ->ownedBy($org)
                                ->forUser([
                                    'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E999',
                                    'given_name'  => 'first',
                                    'family_name' => 'last',
                                ])
                                ->for($document)
                                ->hasFiles(1, [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac2E988',
                                    'name' => 'contract',
                                    'size' => 100,
                                    'path' => 'http://example.com/document.csv',
                                ])
                                ->create([
                                    'id'                => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                    'note'              => 'Note',
                                    'pinned'            => true,
                                    'created_at'        => '2021-07-11T23:27:47+00:00',
                                    'updated_at'        => '2021-07-11T23:27:47+00:00',
                                    'quote_request_id'  => QuoteRequest::factory()->ownedBy($org)->create([
                                        'id' => '50db1187-22f7-473e-8fe4-abbc7a2fa1f2',
                                    ]),
                                    'change_request_id' => ChangeRequest::factory()->ownedBy($org)->create([
                                        'id' => '12b9075f-b0c5-4ee0-bdad-6c6db5ba1b67',
                                    ]),
                                ]);
                            // same org different document
                            Document::factory()
                                ->ownedBy($org)
                                ->hasNotes(1)
                                ->create();
                            // different org
                            Document::factory()
                                ->ownedBy($organization)
                                ->hasNotes(1)
                                ->create();

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
    public function dataProviderQueryChangeRequests(): array {
        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('contract'),
                new OrgUserDataProvider('contract', [
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract'),
                        static function (TestCase $test, Organization $org): Document {
                            return Document::factory()->ownedBy($org)->create([
                                'is_hidden'   => false,
                                'is_contract' => true,
                                'is_quote'    => false,
                            ]);
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('contract'),
                new OrgUserDataProvider(
                    'contract',
                    [
                        'contracts-view',
                    ],
                    '22ca602c-ae8c-41b0-83a0-c6a5e7cf3538',
                ),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('contract', [
                            'changeRequests' => [
                                [
                                    'id'      => '4acb3b3a-82b4-4ae4-8413-cb87c0fed513',
                                    'user_id' => '22ca602c-ae8c-41b0-83a0-c6a5e7cf3538',
                                    'subject' => 'Subject A',
                                    'message' => 'Change Request A',
                                    'from'    => 'user@example.com',
                                    'to'      => ['test@example.com'],
                                    'cc'      => ['cc@example.com'],
                                    'bcc'     => ['bcc@example.com'],
                                    'files'   => [
                                        [
                                            'name' => 'documents.csv',
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                        static function (TestCase $test, Organization $org, User $user): Document {
                            $organization = Organization::factory()->create();
                            $document     = Document::factory()->ownedBy($org)->create([
                                'type_id'     => Type::factory(),
                                'is_hidden'   => false,
                                'is_contract' => true,
                                'is_quote'    => false,
                            ]);

                            ChangeRequest::factory()
                                ->ownedBy($organization)
                                ->for($user)
                                ->create([
                                    'id'          => '681efbcf-0602-4356-ae3e-426d157ca489',
                                    'object_id'   => $document->getKey(),
                                    'object_type' => $document->getMorphClass(),
                                ]);
                            ChangeRequest::factory()
                                ->ownedBy($org)
                                ->for($user)
                                ->hasFiles(1, [
                                    'name' => 'documents.csv',
                                ])
                                ->create([
                                    'id'          => '4acb3b3a-82b4-4ae4-8413-cb87c0fed513',
                                    'object_id'   => $document->getKey(),
                                    'object_type' => $document->getMorphClass(),
                                    'message'     => 'Change Request A',
                                    'subject'     => 'Subject A',
                                    'from'        => 'user@example.com',
                                    'to'          => ['test@example.com'],
                                    'cc'          => ['cc@example.com'],
                                    'bcc'         => ['bcc@example.com'],
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
