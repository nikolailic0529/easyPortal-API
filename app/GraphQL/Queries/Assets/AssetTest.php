<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Assets;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\ChangeRequest;
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
use App\Models\QuoteRequest;
use App\Models\Reseller;
use App\Models\ResellerCustomer;
use App\Models\ResellerLocation;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Date;
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
class AssetTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                               $orgFactory
     * @param UserFactory                                       $userFactory
     * @param SettingsFactory                                   $settingsFactory
     * @param Closure(static, ?Organization, ?User): Asset|null $assetFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $assetFactory = null,
    ): void {
        // Prepare
        $org     = $this->setOrganization($orgFactory);
        $user    = $this->setUser($userFactory, $org);
        $assetId = $assetFactory
            ? $assetFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        $this->setSettings($settingsFactory);

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
                        nickname
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
                        contacts_count
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
                        quoteRequest {
                            id
                            message
                            oem_id
                            oem {
                                id
                                key
                                name
                            }
                            customer_id
                            customer_custom
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
                            contact {
                                name
                                email
                                phone_valid
                            }
                            type_id
                            type {
                                id
                                name
                            }
                            files {
                                name
                            }
                        }
                        changeRequest {
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
                        changed_at
                        synced_at
                    }
                }
            ', ['id' => $assetId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryChangeRequests
     *
     * @param OrganizationFactory                               $orgFactory
     * @param UserFactory                                       $userFactory
     * @param SettingsFactory                                   $settingsFactory
     * @param Closure(static, ?Organization, ?User): Asset|null $assetFactory
     */
    public function testQueryChangeRequests(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $assetFactory = null,
    ): void {
        // Prepare
        $org     = $this->setOrganization($orgFactory);
        $user    = $this->setUser($userFactory, $org);
        $assetId = $assetFactory
            ? $assetFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        $this->setSettings($settingsFactory);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query asset($id: ID!) {
                    asset(id: $id) {
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
            ', ['id' => $assetId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryContracts
     *
     * @param OrganizationFactory                               $orgFactory
     * @param UserFactory                                       $userFactory
     * @param SettingsFactory                                   $settingsFactory
     * @param Closure(static, ?Organization, ?User): Asset|null $assetFactory
     */
    public function testQueryContracts(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $assetFactory = null,
    ): void {
        // Prepare
        $org     = $this->setOrganization($orgFactory);
        $user    = $this->setUser($userFactory, $org);
        $assetId = $assetFactory
            ? $assetFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        $this->setSettings($settingsFactory);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query test($id: ID!) {
                    asset(id: $id) {
                        contracts {
                            id
                            entries_count
                            entries {
                                id
                                asset_id
                            }
                        }
                        contractsAggregated {
                            count
                            groups(groupBy: {type_id: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {type_id: asc}) {
                                count
                            }
                        }
                    }
                }
            ', ['id' => $assetId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryQuotes
     *
     * @param OrganizationFactory                               $orgFactory
     * @param UserFactory                                       $userFactory
     * @param array<mixed>                                      $settingsFactory
     * @param Closure(static, ?Organization, ?User): Asset|null $assetFactory
     */
    public function testQueryQuotes(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $assetFactory = null,
    ): void {
        // Prepare
        $org     = $this->setOrganization($orgFactory);
        $user    = $this->setUser($userFactory, $org);
        $assetId = $assetFactory
            ? $assetFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        $this->setSettings($settingsFactory);

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query test($id: ID!) {
                    asset(id: $id) {
                        quotes {
                            id
                            entries_count
                            entries {
                                id
                                asset_id
                            }
                        }
                        quotesAggregated {
                            count
                            groups(groupBy: {type_id: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {type_id: asc}) {
                                count
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
        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('asset'),
                new OrgUserDataProvider('asset', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('asset'),
                        [],
                        static function (TestCase $test, Organization $org): Asset {
                            return Asset::factory()->ownedBy($org)->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('asset', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
                new OrgUserDataProvider(
                    'asset',
                    [
                        'assets-view',
                    ],
                    'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                ),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('asset', [
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
                            'type'                      => [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
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
                            'location'                  => [
                                'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                'state'     => 'state1',
                                'postcode'  => '19911',
                                'line_one'  => 'line_one_data',
                                'line_two'  => 'line_two_data',
                                'latitude'  => 47.91634204,
                                'longitude' => -2.26318359,
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
                            'warranty_end'              => '2021-01-01',
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
                                    'id'               => 'e4a60a4f-492f-4e16-8fea-d9bd77ed2551',
                                    'reseller_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                    'customer_id'      => null,
                                    'document_id'      => null,
                                    'document_number'  => null,
                                    'start'            => '2021-01-01',
                                    'end'              => '2022-01-01',
                                    'service_group_id' => null,
                                    'serviceGroup'     => null,
                                    'service_level_id' => null,
                                    'serviceLevel'     => null,
                                    'customer'         => null,
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
                                    'type_id'          => '2511521b-8cd3-4bff-b27d-758627f796ef',
                                    'type'             => [
                                        'id'   => '2511521b-8cd3-4bff-b27d-758627f796ef',
                                        'key'  => 'type',
                                        'name' => 'Type',
                                    ],
                                    'status_id'        => '2511521b-8cd3-4bff-b27d-758627f796ef',
                                    'status'           => [
                                        'id'   => '2511521b-8cd3-4bff-b27d-758627f796ef',
                                        'key'  => 'status',
                                        'name' => 'Type',
                                    ],
                                    'description'      => 'warranty description',
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
                            'quoteRequest'              => [
                                'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20952',
                                'type_id'         => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20953',
                                'oem_id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'oem'             => [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'key'  => 'key',
                                    'name' => 'oem1',
                                ],
                                'customer_id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                'customer_custom' => null,
                                'customer'        => [
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
                                'contact'         => [
                                    'name'        => 'contact3',
                                    'email'       => 'contact3@test.com',
                                    'phone_valid' => false,
                                ],
                                'type'            => [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20953',
                                    'name' => 'new',
                                ],
                                'files'           => [],
                                'message'         => null,
                            ],
                            'changeRequest'             => [
                                'id'      => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20963',
                                'user_id' => 'fd421bad-069f-491c-ad5f-5841aa9a9dee',
                                'subject' => 'subject',
                                'message' => 'change request',
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
                            'changed_at'                => '2021-10-19T10:15:00+00:00',
                            'synced_at'                 => '2021-10-19T10:25:00+00:00',
                        ]),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org, User $user): Asset {
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
                                'oem_id' => $oem->id,
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
                                'assets_count'    => 0,
                                'contacts_count'  => 0,
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
                                    'id'             => $org,
                                    'name'           => 'name aaa',
                                    'contacts_count' => 0,
                                    'changed_at'     => '2021-10-19 10:15:00',
                                    'synced_at'      => '2021-10-19 10:25:00',
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

                            // Location belongs to
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

                            // Service
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
                                'number'      => 'b0a1c3e2-95a7-4ef3-a42e-33c3a7c577fe',
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
                                    'warranty_end'              => '2021-01-01',
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

                            // Should be returned - document has valid type
                            $warrantyType   = Type::factory()->create([
                                'id'   => '2511521b-8cd3-4bff-b27d-758627f796ef',
                                'key'  => 'type',
                                'name' => 'Type',
                            ]);
                            $warrantyStatus = Status::factory()->create([
                                'id'   => '2511521b-8cd3-4bff-b27d-758627f796ef',
                                'key'  => 'status',
                                'name' => 'Type',
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
                                    'document_number' => $document->number,
                                    'start'           => '2021-01-01',
                                    'end'             => '2022-01-01',
                                    'type_id'         => $warrantyType,
                                    'status_id'       => $warrantyStatus,
                                    'description'     => 'warranty description',
                                ]);

                            // Should be returned - no document
                            AssetWarranty::factory()
                                ->create([
                                    'id'              => 'e4a60a4f-492f-4e16-8fea-d9bd77ed2551',
                                    'asset_id'        => $asset,
                                    'reseller_id'     => $reseller,
                                    'customer_id'     => null,
                                    'document_id'     => null,
                                    'document_number' => null,
                                    'start'           => '2021-01-01',
                                    'end'             => '2022-01-01',
                                ]);

                            // Should not be returned - document not a contract
                            AssetWarranty::factory()
                                ->create([
                                    'id'          => 'ec0379a8-ecd0-4245-bdb2-71cf58f91b40',
                                    'asset_id'    => $asset,
                                    'reseller_id' => $reseller,
                                    'customer_id' => null,
                                    'document_id' => Document::factory()->create(),
                                    'start'       => '2021-01-01',
                                    'end'         => '2022-01-01',
                                ]);
                            // Quote Requests
                            QuoteRequest::factory()
                                ->ownedBy($org)
                                ->hasAssets(1, [
                                    'asset_id' => $asset->getKey(),
                                ])
                                ->create([
                                    'id'         => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20951',
                                    'created_at' => Date::now()->subHour(),
                                ]);
                            QuoteRequest::factory()
                                ->ownedBy($org)
                                ->for($oem)
                                ->for(Type::factory()->create([
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20953',
                                    'name' => 'new',
                                ]))
                                ->hasAssets(1, [
                                    'asset_id' => $asset->getKey(),
                                ])
                                ->hasContact(1, [
                                    'name'        => 'contact3',
                                    'email'       => 'contact3@test.com',
                                    'phone_valid' => false,
                                ])
                                ->create([
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20952',
                                    'message'         => null,
                                    'customer_id'     => $customer,
                                    'customer_custom' => null,
                                    'created_at'      => Date::now(),
                                ]);
                            // Change Requests
                            ChangeRequest::factory()
                                ->ownedBy($org)
                                ->for($user)
                                ->create([
                                    'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20973',
                                    'object_id'   => $asset->getKey(),
                                    'object_type' => $asset->getMorphClass(),
                                    'created_at'  => Date::now()->subHour(),
                                ]);
                            ChangeRequest::factory()
                                ->ownedBy($org)
                                ->hasFiles(1, [
                                    'name' => 'documents.csv',
                                ])
                                ->for($user)
                                ->create([
                                    'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20963',
                                    'object_id'   => $asset->getKey(),
                                    'object_type' => $asset->getMorphClass(),
                                    'message'     => 'change request',
                                    'subject'     => 'subject',
                                    'from'        => 'user@example.com',
                                    'to'          => ['test@example.com'],
                                    'cc'          => ['cc@example.com'],
                                    'bcc'         => ['bcc@example.com'],
                                    'created_at'  => Date::now(),
                                ]);

                            return $asset;
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
                new OrgRootDataProvider('asset'),
                new OrgUserDataProvider('asset', [
                    'assets-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('asset'),
                        [],
                        static function (TestCase $test, Organization $org): Asset {
                            return Asset::factory()->ownedBy($org)->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('asset'),
                new OrgUserDataProvider(
                    'asset',
                    [
                        'assets-view',
                    ],
                    '22ca602c-ae8c-41b0-83a0-c6a5e7cf3538',
                ),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('asset', [
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
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org, User $user): Asset {
                            $organization = Organization::factory()->create();
                            $asset        = Asset::factory()->ownedBy($org)->create();

                            ChangeRequest::factory()
                                ->ownedBy($organization)
                                ->for($user)
                                ->create([
                                    'id'          => '681efbcf-0602-4356-ae3e-426d157ca489',
                                    'object_id'   => $asset->getKey(),
                                    'object_type' => $asset->getMorphClass(),
                                ]);
                            ChangeRequest::factory()
                                ->ownedBy($org)
                                ->for($user)
                                ->hasFiles(1, [
                                    'name' => 'documents.csv',
                                ])
                                ->create([
                                    'id'          => '4acb3b3a-82b4-4ae4-8413-cb87c0fed513',
                                    'object_id'   => $asset->getKey(),
                                    'object_type' => $asset->getMorphClass(),
                                    'message'     => 'Change Request A',
                                    'subject'     => 'Subject A',
                                    'from'        => 'user@example.com',
                                    'to'          => ['test@example.com'],
                                    'cc'          => ['cc@example.com'],
                                    'bcc'         => ['bcc@example.com'],
                                ]);

                            return $asset;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryContracts(): array {
        $factory = static function (TestCase $test, Organization $org): Asset {
            $type     = Type::factory()->create([
                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
            ]);
            $asset    = Asset::factory()->ownedBy($org)->create([
                'id' => '104d3e00-573b-4b6b-a2c1-07cb8fce4aee',
            ]);
            $document = Document::factory()->ownedBy($org)->create([
                'id'            => '2d275acf-b4a3-43f4-9604-61c82742753b',
                'type_id'       => $type,
                'is_hidden'     => false,
                'is_contract'   => true,
                'is_quote'      => false,
                'entries_count' => 3,
            ]);

            DocumentEntry::factory()->create([
                'id'          => 'c90de82d-73d4-42d1-9bab-f718ffe09bfd',
                'asset_id'    => $asset,
                'document_id' => $document,
            ]);
            DocumentEntry::factory()->create([
                'id'          => '9e45d654-a554-4bf7-89ff-5fe338c12380',
                'asset_id'    => $asset,
                'document_id' => $document,
            ]);
            DocumentEntry::factory()->create([
                'id'          => 'a7925978-c5aa-482c-80b5-c87e3bec66b3',
                'asset_id'    => Asset::factory([
                    'id' => '92323629-fd8f-4f01-8515-8f2960099665',
                ]),
                'document_id' => $document,
            ]);

            return $asset;
        };
        $empty   = [
            'contracts'           => [
                // empty
            ],
            'contractsAggregated' => [
                'count'            => 0,
                'groups'           => [],
                'groupsAggregated' => [
                    'count' => 0,
                ],
            ],
        ];

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('asset'),
                new OrgUserDataProvider('asset', [
                    'assets-view',
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('asset'),
                        [],
                        static function (): Asset {
                            return Asset::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('asset', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('asset', [
                    'assets-view',
                    'contracts-view',
                ]),
                new ArrayDataProvider([
                    'ok'                  => [
                        new GraphQLSuccess(
                            'asset',
                            [
                                'contracts'           => [
                                    [
                                        'id'            => '2d275acf-b4a3-43f4-9604-61c82742753b',
                                        'entries_count' => 3,
                                        'entries'       => [
                                            [
                                                'id'       => '9e45d654-a554-4bf7-89ff-5fe338c12380',
                                                'asset_id' => '104d3e00-573b-4b6b-a2c1-07cb8fce4aee',
                                            ],
                                            [
                                                'id'       => 'a7925978-c5aa-482c-80b5-c87e3bec66b3',
                                                'asset_id' => '92323629-fd8f-4f01-8515-8f2960099665',
                                            ],
                                            [
                                                'id'       => 'c90de82d-73d4-42d1-9bab-f718ffe09bfd',
                                                'asset_id' => '104d3e00-573b-4b6b-a2c1-07cb8fce4aee',
                                            ],
                                        ],
                                    ],
                                ],
                                'contractsAggregated' => [
                                    'count'            => 1,
                                    'groups'           => [
                                        [
                                            'key'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                            'count' => 1,
                                        ],
                                    ],
                                    'groupsAggregated' => [
                                        'count' => 1,
                                    ],
                                ],
                            ],
                        ),
                        [
                            // empty
                        ],
                        $factory,
                    ],
                    'is_hidden = true'    => [
                        new GraphQLSuccess('asset', $empty),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Asset {
                            $asset    = Asset::factory()->ownedBy($org)->create();
                            $document = Document::factory()->ownedBy($org)->create([
                                'is_hidden'   => true,
                                'is_contract' => true,
                                'is_quote'    => false,
                            ]);

                            DocumentEntry::factory()->create([
                                'asset_id'    => $asset,
                                'document_id' => $document,
                            ]);

                            return $asset;
                        },
                    ],
                    'is_contract = false' => [
                        new GraphQLSuccess('asset', $empty),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Asset {
                            $asset    = Asset::factory()->ownedBy($org)->create();
                            $document = Document::factory()->ownedBy($org)->create([
                                'is_hidden'   => false,
                                'is_contract' => false,
                                'is_quote'    => true,
                            ]);

                            DocumentEntry::factory()->create([
                                'asset_id'    => $asset,
                                'document_id' => $document,
                            ]);

                            return $asset;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryQuotes(): array {
        $factory = static function (TestCase $test, Organization $org): Asset {
            $type     = Type::factory()->create([
                'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
            ]);
            $asset    = Asset::factory()->ownedBy($org)->create([
                'id' => '104d3e00-573b-4b6b-a2c1-07cb8fce4aee',
            ]);
            $document = Document::factory()->ownedBy($org)->create([
                'id'            => '2d275acf-b4a3-43f4-9604-61c82742753b',
                'type_id'       => $type,
                'is_hidden'     => false,
                'is_quote'      => true,
                'entries_count' => 3,
            ]);

            DocumentEntry::factory()->create([
                'id'          => 'c90de82d-73d4-42d1-9bab-f718ffe09bfd',
                'asset_id'    => $asset,
                'document_id' => $document,
            ]);
            DocumentEntry::factory()->create([
                'id'          => '9e45d654-a554-4bf7-89ff-5fe338c12380',
                'asset_id'    => $asset,
                'document_id' => $document,
            ]);
            DocumentEntry::factory()->create([
                'id'          => 'a7925978-c5aa-482c-80b5-c87e3bec66b3',
                'asset_id'    => Asset::factory([
                    'id' => '92323629-fd8f-4f01-8515-8f2960099665',
                ]),
                'document_id' => $document,
            ]);

            return $asset;
        };
        $quotes  = [
            'quotes'           => [
                [
                    'id'            => '2d275acf-b4a3-43f4-9604-61c82742753b',
                    'entries_count' => 3,
                    'entries'       => [
                        [
                            'id'       => '9e45d654-a554-4bf7-89ff-5fe338c12380',
                            'asset_id' => '104d3e00-573b-4b6b-a2c1-07cb8fce4aee',
                        ],
                        [
                            'id'       => 'a7925978-c5aa-482c-80b5-c87e3bec66b3',
                            'asset_id' => '92323629-fd8f-4f01-8515-8f2960099665',
                        ],
                        [
                            'id'       => 'c90de82d-73d4-42d1-9bab-f718ffe09bfd',
                            'asset_id' => '104d3e00-573b-4b6b-a2c1-07cb8fce4aee',
                        ],
                    ],
                ],
            ],
            'quotesAggregated' => [
                'count'            => 1,
                'groups'           => [
                    [
                        'key'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        'count' => 1,
                    ],
                ],
                'groupsAggregated' => [
                    'count' => 1,
                ],
            ],
        ];
        $empty   = [
            'quotes'           => [
                // empty
            ],
            'quotesAggregated' => [
                'count'            => 0,
                'groups'           => [],
                'groupsAggregated' => [
                    'count' => 0,
                ],
            ],
        ];

        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('asset'),
                new OrgUserDataProvider('asset', [
                    'customers-view',
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('asset'),
                        [],
                        static function (): Asset {
                            return Asset::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('asset', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('asset', [
                    'customers-view',
                    'quotes-view',
                ]),
                new ArrayDataProvider([
                    'ok'               => [
                        new GraphQLSuccess('asset', $quotes),
                        [
                            // empty
                        ],
                        $factory,
                    ],
                    'is_hidden = true' => [
                        new GraphQLSuccess('asset', $empty),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Asset {
                            $asset    = Asset::factory()->ownedBy($org)->create();
                            $document = Document::factory()->ownedBy($org)->create([
                                'is_hidden' => true,
                                'is_quote'  => true,
                            ]);

                            DocumentEntry::factory()->create([
                                'asset_id'    => $asset,
                                'document_id' => $document,
                            ]);

                            return $asset;
                        },
                    ],
                    'is_quote = false' => [
                        new GraphQLSuccess('asset', $empty),
                        [
                            // empty
                        ],
                        static function (TestCase $test, Organization $org): Asset {
                            $asset    = Asset::factory()->ownedBy($org)->create();
                            $document = Document::factory()->ownedBy($org)->create([
                                'is_hidden' => false,
                                'is_quote'  => false,
                            ]);

                            DocumentEntry::factory()->create([
                                'asset_id'    => $asset,
                                'document_id' => $document,
                            ]);

                            return $asset;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
