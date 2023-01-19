<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Location;
use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
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

/**
 * @internal
 * @coversNothing
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class AssetsTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                                              $orgFactory
     * @param UserFactory                                                      $userFactory
     * @param SettingsFactory                                                  $settingsFactory
     * @param Closure(static, ?Organization, ?User): array<string, mixed>|null $customerFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $org   = $this->setOrganization($orgFactory);
        $user  = $this->setUser($userFactory, $org);
        $where = $customerFactory
            ? $customerFactory($this, $org, $user)
            : null;

        $this->setSettings($settingsFactory);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query assets($where: SearchByConditionAssetsQuery) {
                    assets(where: $where) {
                        id
                        oem_id
                        product_id
                        type_id
                        customer_id
                        location_id
                        serial_number
                        nickname
                        contacts_count
                        data_quality
                        contracts_active_quantity
                        eosl
                        oem {
                            id
                            key
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
                                key
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
                        reseller_id
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
                        location {
                            id
                            state
                            postcode
                            line_one
                            line_two
                            latitude
                            longitude
                        }
                        warranty_end
                        warranty_changed_at
                        warranty_service_group_id
                        warrantyServiceGroup {
                            id
                            oem_id
                            sku
                            name
                        }
                        warranty_service_level_id
                        warrantyServiceLevel {
                            id
                            oem_id
                            sku
                            name
                            description
                        }
                        warranties {
                            id
                            reseller_id
                            customer_id
                            document_id
                            document_number
                            start
                            end
                            service_group_id
                            serviceGroup {
                                id
                                oem_id
                                sku
                                name
                            }
                            service_level_id
                            serviceLevel {
                                id
                                oem_id
                                sku
                                name
                                description
                            }
                            customer {
                                id
                                name
                                assets_count
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
                                contacts_count
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
                            type_id
                            type {
                                id
                                key
                                name
                            }
                            status_id
                            status {
                                id
                                key
                                name
                            }
                            description
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
                        coverages_count
                        coverages {
                            id
                            name
                        }
                        tags {
                            id
                            name
                        }
                        changed_at
                        synced_at
                    }
                    assetsAggregated(where: $where) {
                        count
                        groups(groupBy: {product_id: asc}) {
                            key
                            count
                        }
                        groupsAggregated(groupBy: {product_id: asc}) {
                            count
                        }
                    }
                }
            ', ['where' => $where])
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
                new OrgRootDataProvider('assets'),
                new OrgUserDataProvider('assets', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated('assets'),
                        [],
                        static function (): array {
                            return [];
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('assets', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
                new OrgUserDataProvider('assets', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLPaginated(
                            'assets',
                            [
                                [
                                    'id'                        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'oem_id'                    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'product_id'                => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                    'location_id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                    'type_id'                   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    'customer_id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                    'serial_number'             => '#PRODUCT_SERIAL_323',
                                    'nickname'                  => 'nickname123',
                                    'contacts_count'            => 1,
                                    'data_quality'              => '130',
                                    'contracts_active_quantity' => 321,
                                    'eosl'                      => null,
                                    'oem'                       => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'key'  => 'key',
                                        'name' => 'oem1',
                                    ],
                                    'product'                   => [
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
                                    'type'                      => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                        'name' => 'name aaa',
                                    ],
                                    'location'                  => [
                                        'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                        'state'     => 'state1',
                                        'postcode'  => '19911',
                                        'line_one'  => 'line_one_data',
                                        'line_two'  => 'line_two_data',
                                        'latitude'  => 47.91634204,
                                        'longitude' => -2.26318359,
                                    ],
                                    'customer'                  => [
                                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                        'name'            => 'name aaa',
                                        'assets_count'    => 0,
                                        'locations_count' => 0,
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
                                    'reseller_id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                    'reseller'                  => [
                                        'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                        'name'            => 'reseller1',
                                        'customers_count' => 0,
                                        'locations_count' => 0,
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
                                    'warranty_end'              => '2022-01-01',
                                    'warranty_changed_at'       => '2021-10-19T10:25:00+00:00',
                                    'warranty_service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                    'warrantyServiceGroup'      => [
                                        'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                        'name'   => 'Group',
                                        'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'sku'    => 'SKU#123',
                                    ],
                                    'warranty_service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                    'warrantyServiceLevel'      => [
                                        'id'          => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                        'name'        => 'Level',
                                        'oem_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'sku'         => 'SKU#123',
                                        'description' => 'description',
                                    ],
                                    'warranties'                => [
                                        [
                                            'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                            'reseller_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                            'customer_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                            'document_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                            'document_number'  => 'b0a1c3e2-95a7-4ef3-a42e-33c3a7c577fe',
                                            'start'            => '2021-01-01',
                                            'end'              => '2022-01-01',
                                            'service_group_id' => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                            'serviceGroup'     => [
                                                'id'     => '8b4d2d12-542a-4fcf-9acc-626bfb5dbc79',
                                                'name'   => 'Group',
                                                'oem_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                'sku'    => 'SKU#123',
                                            ],
                                            'service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                            'serviceLevel'     => [
                                                'id'          => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                                'name'        => 'Level',
                                                'oem_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                                'sku'         => 'SKU#123',
                                                'description' => 'description',
                                            ],
                                            'customer'         => [
                                                'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                                'name'            => 'name aaa',
                                                'assets_count'    => 0,
                                                'locations_count' => 0,
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
                                            'reseller'         => [
                                                'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                                'name'            => 'reseller1',
                                                'customers_count' => 0,
                                                'locations_count' => 0,
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
                                            'type_id'          => null,
                                            'type'             => null,
                                            'status_id'        => null,
                                            'status'           => null,
                                            'description'      => null,
                                        ],
                                    ],
                                    'status'                    => [
                                        'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                        'name' => 'active',
                                    ],
                                    'contacts'                  => [
                                        [
                                            'name'        => 'contact2',
                                            'email'       => 'contact2@test.com',
                                            'phone_valid' => false,
                                        ],
                                    ],
                                    'coverages_count'           => 1,
                                    'coverages'                 => [
                                        [
                                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20948',
                                            'name' => 'COVERED_ON_CONTRACT',
                                        ],
                                    ],
                                    'tags'                      => [
                                        [
                                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20950',
                                            'name' => 'Software',
                                        ],
                                    ],
                                    'changed_at'                => '2021-10-19T10:15:00+00:00',
                                    'synced_at'                 => '2021-10-19T10:25:00+00:00',
                                ],
                            ],
                            [
                                'count'            => 1,
                                'groups'           => [
                                    [
                                        'key'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                        'count' => 1,
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 1,
                                ],
                            ],
                        ),
                        [
                            // empty,
                        ],
                        static function (TestCase $test, Organization $org): array {
                            // OEM Creation belongs to
                            $oem = Oem::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'key'  => 'key',
                                'name' => 'oem1',
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

                            // Type Creation belongs to
                            $type     = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
                            ]);
                            $reseller = Reseller::factory()->create([
                                'id'              => $org,
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 0,
                                'contacts_count'  => 0,
                                'assets_count'    => 0,
                                'changed_at'      => '2021-10-19 10:15:00',
                                'synced_at'       => '2021-10-19 10:25:00',
                            ]);
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
                                    'contacts_count'  => 0,
                                    'locations_count' => 0,
                                    'changed_at'      => '2021-10-19 10:15:00',
                                    'synced_at'       => '2021-10-19 10:25:00',
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

                            ResellerCustomer::factory()->create([
                                'reseller_id' => $reseller,
                                'customer_id' => $customer,
                            ]);

                            ResellerLocation::factory()->create([
                                'reseller_id' => $reseller,
                                'location_id' => $location,
                            ]);
                            CustomerLocation::factory()->create([
                                'customer_id' => $customer,
                                'location_id' => $location,
                            ]);

                            // Location belongs to Asset
                            $assetLocation = Location::factory()->create([
                                'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                'state'     => 'state1',
                                'postcode'  => '19911',
                                'line_one'  => 'line_one_data',
                                'line_two'  => 'line_two_data',
                                'latitude'  => '47.91634204',
                                'longitude' => '-2.26318359',
                            ]);

                            $assetLocation->resellers()->attach($reseller);
                            $assetLocation->customers()->attach($customer);

                            // Service Group/Level
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

                            // Document creation for support
                            $document = Document::factory()->ownedBy($org)->create([
                                'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                'is_hidden'   => false,
                                'is_contract' => true,
                                'is_quote'    => false,
                                'reseller_id' => $reseller,
                            ]);
                            // Document entry creation for services
                            DocumentEntry::factory()->create([
                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'document_id'      => $document,
                                'asset_id'         => Asset::factory()->create([
                                    'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24999',
                                ]),
                                'product_id'       => $product,
                                'service_level_id' => $serviceLevel,
                            ]);
                            // Status belongs to
                            $status = Status::factory()->create([
                                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                'name'        => 'active',
                                'key'         => 'active',
                                'object_type' => (new Asset())->getMorphClass(),
                            ]);
                            // Asset Creation
                            $asset = Asset::factory()
                                ->for($oem)
                                ->for($product)
                                ->for($reseller)
                                ->for($customer)
                                ->for($type)
                                ->for($assetLocation)
                                ->for($status)
                                ->hasTags(1, [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20950',
                                    'name' => 'Software',
                                ])
                                ->hasContacts(1, [
                                    'name'        => 'contact2',
                                    'email'       => 'contact2@test.com',
                                    'phone_valid' => false,
                                ])
                                ->hasCoverages(1, [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20948',
                                    'name' => 'COVERED_ON_CONTRACT',
                                ])
                                ->create([
                                    'id'                        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'serial_number'             => '#PRODUCT_SERIAL_323',
                                    'nickname'                  => 'nickname123',
                                    'warranty_end'              => '2022-01-01',
                                    'warranty_changed_at'       => '2021-10-19 10:25:00',
                                    'warranty_service_group_id' => $serviceGroup,
                                    'warranty_service_level_id' => $serviceLevel,
                                    'contacts_count'            => 1,
                                    'coverages_count'           => 1,
                                    'data_quality'              => '130',
                                    'contracts_active_quantity' => 321,
                                    'eosl'                      => null,
                                    'changed_at'                => '2021-10-19 10:15:00',
                                    'synced_at'                 => '2021-10-19 10:25:00',
                                ]);

                            AssetWarranty::factory()
                                ->for($serviceGroup)
                                ->for($serviceLevel)
                                ->create([
                                    'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                    'asset_id'        => $asset,
                                    'reseller_id'     => $reseller,
                                    'customer_id'     => $customer,
                                    'document_id'     => $document,
                                    'document_number' => 'b0a1c3e2-95a7-4ef3-a42e-33c3a7c577fe',
                                    'start'           => '2021-01-01',
                                    'end'             => '2022-01-01',
                                ]);

                            Asset::factory()->create([
                                'reseller_id' => $reseller,
                                'customer_id' => $customer,
                            ]);

                            return [
                                'id' => ['equal' => $asset->getKey()],
                            ];
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
