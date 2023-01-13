<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Customers;

use App\Models\Asset;
use App\Models\AssetWarranty;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\CustomerLocation;
use App\Models\Data\Currency;
use App\Models\Data\Language;
use App\Models\Data\Location;
use App\Models\Data\Oem;
use App\Models\Data\Product;
use App\Models\Data\ServiceGroup;
use App\Models\Data\ServiceLevel;
use App\Models\Data\Status;
use App\Models\Data\Type;
use App\Models\Distributor;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Kpi;
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
use Tests\DataProviders\GraphQL\Organizations\AuthOrgResellerDataProvider;
use Tests\DataProviders\GraphQL\Organizations\OrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Assets\AssetsAggregated
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class CustomerTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param SettingsFactory                                      $settingsFactory
     * @param Closure(static, ?Organization, ?User): Customer|null $customerFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this, $org, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
                        id
                        name
                        assets_count
                        quotes_count
                        contracts_count
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
                        headquarter {
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
                        statuses {
                          id
                          key
                          name
                        }
                        kpi {
                            assets_total
                            assets_active
                            assets_active_percent
                            assets_active_on_contract
                            assets_active_on_warranty
                            assets_active_exposed
                            customers_active
                            customers_active_new
                            contracts_active
                            contracts_active_amount
                            contracts_active_new
                            contracts_expiring
                            contracts_expired
                            quotes_active
                            quotes_active_amount
                            quotes_active_new
                            quotes_expiring
                            quotes_expired
                            quotes_ordered
                            quotes_accepted
                            quotes_requested
                            quotes_received
                            quotes_rejected
                            quotes_awaiting
                            service_revenue_total_amount
                            service_revenue_total_amount_change
                        }
                        changed_at
                        synced_at
                    }
                }
            ', ['id' => $customerId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryAssets
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param SettingsFactory                                      $settingsFactory
     * @param Closure(static, ?Organization, ?User): Customer|null $customerFactory
     */
    public function testQueryAssets(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        $customerId = 'wrong';

        if ($customerFactory) {
            $customerId = $customerFactory($this, $org, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
                        assets {
                            id
                            oem_id
                            product_id
                            type_id
                            customer_id
                            location_id
                            serial_number
                            data_quality
                            contracts_active_quantity
                            eosl
                            customer {
                                id
                                name
                                assets_count
                                quotes_count
                                contracts_count
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
                            status {
                              id
                              name
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
                                    quotes_count
                                    contracts_count
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
                            contacts_count
                            contacts {
                                name
                                email
                                phone_valid
                            }
                            status {
                                id
                                name
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
                        assetsAggregated {
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
                }
            ', ['id' => $customerId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryContracts
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param SettingsFactory                                      $settingsFactory
     * @param Closure(static, ?Organization, ?User): Customer|null $customerFactory
     */
    public function testQueryContracts(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $org, $user)->getKey();
        }

        // Not empty?
        if ($expected instanceof GraphQLSuccess) {
            self::assertGreaterThan(0, Document::query()->withoutGlobalScopes()->count());
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
                        contracts {
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
                                quotes_count
                                contracts_count
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
                        contractsAggregated {
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
                }
            ', ['id' => $customerId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryQuotes
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param array<mixed>                                         $settingsFactory
     * @param Closure(static, ?Organization, ?User): Customer|null $customerFactory
     */
    public function testQueryQuotes(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $org, $user)->getKey();
        }

        // Not empty?
        if ($expected instanceof GraphQLSuccess) {
            self::assertGreaterThan(0, Document::query()->withoutGlobalScopes()->count());
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
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
                                quotes_count
                                contracts_count
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
                }
            ', ['id' => $customerId])
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryAssetAggregate
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param Closure(static, ?Organization, ?User): Customer|null $customerFactory
     * @param array<string, mixed>                                 $params
     */
    public function testQueryAssetAggregated(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $customerFactory = null,
        array $params = [],
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $org, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!, $where: SearchByConditionAssetsQuery) {
                    customer(id: $id) {
                        assetsAggregated(where: $where) {
                            count
                            groups(groupBy: {type_id: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {type_id: asc}) {
                                count
                            }
                            types {
                                count
                                type_id
                                type {
                                    id
                                    key
                                    name
                                }
                            }
                            coverages {
                                count
                                coverage_id
                                coverage {
                                    id
                                    key
                                    name
                                }
                            }
                        }
                    }
                }
            ', ['id' => $customerId] + $params)
            ->assertThat($expected);
    }

    /**
     * @covers       \App\GraphQL\Queries\Contracts\ContractsAggregated::prices
     *
     * @dataProvider dataProviderQueryContractsAggregate
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param array<string,mixed>                                  $settingsFactory
     * @param Closure(static, ?Organization, ?User): Customer|null $customerFactory
     * @param array<string, mixed>                                 $params
     */
    public function testQueryContractsAggregated(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $customerFactory = null,
        array $params = [],
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        $customerId = 'wrong';
        if ($customerFactory) {
            $customerId = $customerFactory($this, $org, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!, $where: SearchByConditionDocumentsQuery) {
                    customer(id: $id) {
                        contractsAggregated(where: $where) {
                            count
                            groups(groupBy: {currency_id: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {currency_id: asc}) {
                                count
                            }
                            prices {
                                count
                                amount
                                currency_id
                                currency {
                                    id
                                    name
                                    code
                                }
                            }
                        }
                    }
                }
            ', ['id' => $customerId] + $params)
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryChangeRequests
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param Closure(static, ?Organization, ?User): Customer|null $customerFactory
     */
    public function testQueryChangeRequests(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $customerFactory = null,
    ): void {
        // Prepare
        $org        = $this->setOrganization($orgFactory);
        $user       = $this->setUser($userFactory, $org);
        $customerId = $customerFactory
            ? $customerFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query customer($id: ID!) {
                    customer(id: $id) {
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
            ', ['id' => $customerId])
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQueryAssets(): array {
        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('customer'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer'),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgResellerDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok'          => [
                        new GraphQLSuccess('customer', [
                            'assets'           => [
                                [
                                    'id'                        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'oem_id'                    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                    'product_id'                => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                    'location_id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                    'type_id'                   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                                    'customer_id'               => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'serial_number'             => '#PRODUCT_SERIAL_323',
                                    'data_quality'              => '130',
                                    'contracts_active_quantity' => 321,
                                    'eosl'                      => null,
                                    'contacts_count'            => 1,
                                    'customer'                  => [
                                        'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                        'name'            => 'name aaa',
                                        'assets_count'    => 0,
                                        'quotes_count'    => 0,
                                        'contracts_count' => 0,
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
                                    'oem'                       => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'key'  => 'key',
                                        'name' => 'oem1',
                                    ],
                                    'type'                      => [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
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
                                            'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                            'reseller_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                            'customer_id'      => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                            'document_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                            'document_number'  => 'f2f43b22-cc9a-4971-9342-b1389979c008',
                                            'start'            => '2021-01-01',
                                            'end'              => '2022-01-01',
                                            'customer'         => [
                                                'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                                'name'            => 'name aaa',
                                                'assets_count'    => 0,
                                                'quotes_count'    => 0,
                                                'contracts_count' => 0,
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
                                            'reseller'         => [
                                                'id'              => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                                'name'            => 'reseller1',
                                                'customers_count' => 0,
                                                'locations_count' => 1,
                                                'assets_count'    => 0,
                                                'locations'       => [
                                                    [
                                                        'location_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
                                                        'location'    => [
                                                            'id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24984',
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
                                    'contacts'                  => [
                                        [
                                            'name'        => 'contact2',
                                            'email'       => 'contact2@test.com',
                                            'phone_valid' => false,
                                        ],
                                    ],
                                    'status'                    => [
                                        'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                        'name' => 'active',
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
                            'assetsAggregated' => [
                                'count'            => 1,
                                'groups'           => [
                                    [
                                        'key'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                        'count' => 1,
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 1,
                                ],
                            ],
                        ]),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [
                                'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): Customer {
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
                                'id'              => $organization->getKey(),
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 1,
                                'assets_count'    => 0,
                                'contacts_count'  => 0,
                                'changed_at'      => '2021-10-19 10:15:00',
                                'synced_at'       => '2021-10-19 10:25:00',
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
                                    'quotes_count'    => 2,
                                    'contracts_count' => 2,
                                    'contacts_count'  => 1,
                                    'locations_count' => 1,
                                    'changed_at'      => '2021-10-19 10:15:00',
                                    'synced_at'       => '2021-10-19 10:25:00',
                                ]);

                            $customer->resellers()->attach($reseller, [
                                'assets_count'    => 0,
                                'quotes_count'    => 0,
                                'contracts_count' => 0,
                            ]);

                            CustomerLocation::factory()->create([
                                'customer_id' => $customer,
                                'location_id' => $location,
                            ]);

                            // OEM Creation belongs to
                            $oem = Oem::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'key'  => 'key',
                                'name' => 'oem1',
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

                            ResellerLocation::factory()->create([
                                'reseller_id' => $reseller,
                                'location_id' => $assetLocation,
                            ]);

                            $assetLocation->resellers()->attach($reseller);

                            // Product creation belongs to
                            $product = Product::factory()->create([
                                'id'     => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24983',
                                'name'   => 'Product1',
                                'oem_id' => $oem->getKey(),
                                'sku'    => 'SKU#123',
                                'eol'    => '2022-12-30',
                                'eos'    => '2022-01-01',
                            ]);
                            // Type Creation belongs to
                            $type = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24980',
                                'name' => 'name aaa',
                            ]);
                            // Status Creation belongs to
                            $status = Status::factory()->create([
                                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                'name'        => 'active',
                                'key'         => 'active',
                                'object_type' => (new Asset())->getMorphClass(),
                            ]);

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
                            $documentType = Type::factory()->create([
                                'id' => 'f3cb1fac-b454-4f23-bbb4-f3d84a1690ae',
                            ]);
                            $document     = Document::factory()
                                ->for($reseller)
                                ->for($customer)
                                ->create([
                                    'id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                    'type_id' => $documentType,
                                    'number'  => 'f2f43b22-cc9a-4971-9342-b1389979c008',
                                ]);
                            // Asset creation
                            $asset = Asset::factory()
                                ->for($oem)
                                ->for($product)
                                ->for($customer)
                                ->for($type)
                                ->for($status)
                                ->for($assetLocation)
                                ->for($reseller)
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
                            // Document entry creation for services
                            DocumentEntry::factory()->create([
                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                'document_id'      => $document,
                                'asset_id'         => $asset,
                                'product_id'       => $product,
                                'service_level_id' => $serviceLevel,
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
                                ]);

                            return $customer;
                        },
                    ],
                    'not allowed' => [
                        new GraphQLSuccess('customer'),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            $customer = Customer::factory()->create();

                            return $customer;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('customer'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer'),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgResellerDataProvider('customer'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer', [
                            'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                            'name'            => 'name aaa',
                            'assets_count'    => 0,
                            'quotes_count'    => 0,
                            'contracts_count' => 0,
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
                                    'types'       => [
                                        [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                            'name' => 'headquarter',
                                        ],
                                    ],
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
                            'headquarter'     => [
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
                                'types'       => [
                                    [
                                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                        'name' => 'headquarter',
                                    ],
                                ],
                            ],
                            'statuses'        => [
                                [
                                    'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                    'key'  => 'active',
                                    'name' => 'active',
                                ],
                            ],
                            'kpi'             => [
                                'assets_total'                        => 1_001,
                                'assets_active'                       => 1_002,
                                'assets_active_percent'               => 1_003.0,
                                'assets_active_on_contract'           => 1_004,
                                'assets_active_on_warranty'           => 1_005,
                                'assets_active_exposed'               => 1_006,
                                'customers_active'                    => 1_007,
                                'customers_active_new'                => 1_008,
                                'contracts_active'                    => 1_009,
                                'contracts_active_amount'             => 1_0010.0,
                                'contracts_active_new'                => 1_0011,
                                'contracts_expiring'                  => 1_0012,
                                'contracts_expired'                   => 1_0013,
                                'quotes_active'                       => 1_0014,
                                'quotes_active_amount'                => 1_0015.0,
                                'quotes_active_new'                   => 1_0016,
                                'quotes_expiring'                     => 1_0017,
                                'quotes_expired'                      => 1_0018,
                                'quotes_ordered'                      => 1_0019,
                                'quotes_accepted'                     => 1_0020,
                                'quotes_requested'                    => 1_0021,
                                'quotes_received'                     => 1_0022,
                                'quotes_rejected'                     => 1_0023,
                                'quotes_awaiting'                     => 1_0024,
                                'service_revenue_total_amount'        => 1_0025.0,
                                'service_revenue_total_amount_change' => 1_0026.0,
                            ],
                            'changed_at'      => '2021-10-19T10:15:00+00:00',
                            'synced_at'       => '2021-10-19T10:25:00+00:00',
                        ]),
                        [
                            'ep.headquarter_type' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization): Customer {
                            /** @var Reseller $reseller */
                            $reseller = Reseller::factory()->create([
                                'id' => $organization,
                            ]);

                            /** @var \App\Models\Data\Location $location */
                            $location = Location::factory()->create([
                                'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'     => 'state1',
                                'postcode'  => '19911',
                                'line_one'  => 'line_one_data',
                                'line_two'  => 'line_two_data',
                                'latitude'  => '47.91634204',
                                'longitude' => '-2.26318359',
                            ]);

                            $location->resellers()->attach($reseller);

                            $kpi = Kpi::factory()->create([
                                'assets_total'                        => 1,
                                'assets_active'                       => 2,
                                'assets_active_percent'               => 3.0,
                                'assets_active_on_contract'           => 4,
                                'assets_active_on_warranty'           => 5,
                                'assets_active_exposed'               => 6,
                                'customers_active'                    => 7,
                                'customers_active_new'                => 8,
                                'contracts_active'                    => 9,
                                'contracts_active_amount'             => 10.0,
                                'contracts_active_new'                => 11,
                                'contracts_expiring'                  => 12,
                                'contracts_expired'                   => 13,
                                'quotes_active'                       => 14,
                                'quotes_active_amount'                => 15.0,
                                'quotes_active_new'                   => 16,
                                'quotes_expiring'                     => 17,
                                'quotes_expired'                      => 18,
                                'quotes_ordered'                      => 19,
                                'quotes_accepted'                     => 20,
                                'quotes_requested'                    => 21,
                                'quotes_received'                     => 22,
                                'quotes_rejected'                     => 23,
                                'quotes_awaiting'                     => 24,
                                'service_revenue_total_amount'        => 25.0,
                                'service_revenue_total_amount_change' => 26.0,
                            ]);

                            /** @var Customer $customer */
                            $customer    = Customer::factory()
                                ->hasContacts(1, [
                                    'name'        => 'contact1',
                                    'email'       => 'contact1@test.com',
                                    'phone_valid' => false,
                                ])
                                ->hasStatuses(1, [
                                    'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                    'name'        => 'active',
                                    'key'         => 'active',
                                    'object_type' => (new Customer())->getMorphClass(),
                                ])
                                ->create([
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'kpi_id'          => $kpi,
                                    'assets_count'    => 2,
                                    'quotes_count'    => 2,
                                    'contracts_count' => 2,
                                    'contacts_count'  => 1,
                                    'locations_count' => 1,
                                    'changed_at'      => '2021-10-19 10:15:00',
                                    'synced_at'       => '2021-10-19 10:25:00',
                                ]);
                            $customerKpi = Kpi::factory()->create([
                                'assets_total'                        => 1_001,
                                'assets_active'                       => 1_002,
                                'assets_active_percent'               => 1_003.0,
                                'assets_active_on_contract'           => 1_004,
                                'assets_active_on_warranty'           => 1_005,
                                'assets_active_exposed'               => 1_006,
                                'customers_active'                    => 1_007,
                                'customers_active_new'                => 1_008,
                                'contracts_active'                    => 1_009,
                                'contracts_active_amount'             => 1_0010.0,
                                'contracts_active_new'                => 1_0011,
                                'contracts_expiring'                  => 1_0012,
                                'contracts_expired'                   => 1_0013,
                                'quotes_active'                       => 1_0014,
                                'quotes_active_amount'                => 1_0015.0,
                                'quotes_active_new'                   => 1_0016,
                                'quotes_expiring'                     => 1_0017,
                                'quotes_expired'                      => 1_0018,
                                'quotes_ordered'                      => 1_0019,
                                'quotes_accepted'                     => 1_0020,
                                'quotes_requested'                    => 1_0021,
                                'quotes_received'                     => 1_0022,
                                'quotes_rejected'                     => 1_0023,
                                'quotes_awaiting'                     => 1_0024,
                                'service_revenue_total_amount'        => 1_0025.0,
                                'service_revenue_total_amount_change' => 1_0026.0,
                            ]);

                            $customer->resellers()->attach($reseller, [
                                'assets_count'    => 0,
                                'quotes_count'    => 0,
                                'contracts_count' => 0,
                                'kpi_id'          => $customerKpi->getKey(),
                            ]);

                            CustomerLocation::factory()
                                ->hasTypes(1, [
                                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                    'name' => 'headquarter',
                                ])
                                ->create([
                                    'customer_id' => $customer,
                                    'location_id' => $location,
                                ]);

                            return $customer;
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
        $customerContractFactory = static function (TestCase $test, Organization $organization): Customer {
            $reseller = Reseller::factory()->create([
                'id'              => $organization->getKey(),
                'name'            => 'reseller1',
                'customers_count' => 0,
                'locations_count' => 1,
                'assets_count'    => 0,
                'contacts_count'  => 0,
                'changed_at'      => '2021-10-19 10:15:00',
                'synced_at'       => '2021-10-19 10:25:00',
            ]);
            Document::factory()->for($reseller)->create();
            $customer = Customer::factory()
                ->create([
                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                    'name'            => 'name aaa',
                    'assets_count'    => 0,
                    'quotes_count'    => 0,
                    'contracts_count' => 0,
                    'contacts_count'  => 0,
                    'locations_count' => 0,
                ]);
            $customer->resellers()->attach($reseller);

            return $customer;
        };

        $customerEmptyContract = [
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
                new OrgRootDataProvider('customer'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer'),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            Document::factory()->create();

                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgResellerDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok'             => [
                        new GraphQLSuccess(
                            'customer',
                            [
                                'contracts'           => [
                                    [
                                        'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                        'oem_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                        'customer_id'    => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                        'type_id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                        'reseller_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                        'currency_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                        'language_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                                        'distributor_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                                        'is_contract'    => true,
                                        'is_quote'       => false,
                                        'number'         => '1323',
                                        'price'          => 100,
                                        'start'          => '2021-01-01',
                                        'end'            => '2024-01-01',
                                        'oem'            => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                            'key'  => 'key',
                                            'name' => 'oem1',
                                        ],
                                        'oem_said'       => null,
                                        'oem_amp_id'     => 'AMP-ID-123',
                                        'oem_sar_number' => 'SAR-123',
                                        'oemGroup'       => null,
                                        'type'           => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                            'name' => 'name aaa',
                                        ],
                                        'statuses_count' => 1,
                                        'statuses'       => [
                                            [
                                                'id'   => '126042b6-2bc7-4009-9366-b4c95a94c73b',
                                                'name' => 'status a',
                                            ],
                                        ],
                                        'customer'       => [
                                            'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                            'name'            => 'name aaa',
                                            'assets_count'    => 0,
                                            'quotes_count'    => 0,
                                            'contracts_count' => 0,
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
                                        'reseller'       => [
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
                                        'currency'       => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                            'name' => 'Currency1',
                                            'code' => 'CUR',
                                        ],
                                        'entries_count'  => 2,
                                        'entries'        => [
                                            [
                                                'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                                'service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                                                'document_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                                'list_price'       => 67.12,
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
                                                'removed_at'       => null,
                                            ],
                                        ],
                                        'language'       => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                                            'name' => 'Lang1',
                                            'code' => 'en',
                                        ],
                                        'contacts_count' => 3,
                                        'contacts'       => [
                                            [
                                                'name'        => 'contact3',
                                                'email'       => 'contact3@test.com',
                                                'phone_valid' => false,
                                            ],
                                        ],
                                        'distributor'    => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                                            'name' => 'distributor1',
                                        ],
                                        'assets_count'   => 1,
                                        'changed_at'     => '2021-10-19T10:15:00+00:00',
                                        'synced_at'      => '2021-10-19T10:25:00+00:00',
                                    ],
                                ],
                                'contractsAggregated' => [
                                    'count'            => 1,
                                    'groups'           => [
                                        [
                                            'key'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
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
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        static function (TestCase $test, Organization $organization, User $user): Customer {
                            $user->save();

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
                                'id'              => $organization->getKey(),
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
                                    'quotes_count'    => 2,
                                    'contracts_count' => 2,
                                    'contacts_count'  => 1,
                                    'locations_count' => 1,
                                    'changed_at'      => '2021-10-19 10:15:00',
                                    'synced_at'       => '2021-10-19 10:25:00',
                                ]);
                            $customer->resellers()->attach($reseller, [
                                'assets_count'    => 0,
                                'quotes_count'    => 0,
                                'contracts_count' => 0,
                            ]);

                            CustomerLocation::factory()->create([
                                'customer_id' => $customer,
                                'location_id' => $location,
                            ]);

                            // OEM Creation belongs to
                            $oem = Oem::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                                'key'  => 'key',
                                'name' => 'oem1',
                            ]);
                            // Type Creation belongs to
                            $type = Type::factory()->create([
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'name aaa',
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
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
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

                            Document::factory()
                                ->for($oem)
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
                                ->hasEntries(1, [
                                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                                    'asset_id'         => Asset::factory()->create([
                                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                                        'reseller_id' => $reseller,
                                    ]),
                                    'serial_number'    => null,
                                    'product_id'       => $product,
                                    'service_group_id' => $serviceGroup,
                                    'service_level_id' => $serviceLevel,
                                    'list_price'       => 67.12,
                                    'renewal'          => 24.20,
                                    'start'            => '2021-01-01',
                                    'end'              => '2024-01-01',
                                ])
                                ->hasContacts(1, [
                                    'name'        => 'contact3',
                                    'email'       => 'contact3@test.com',
                                    'phone_valid' => false,
                                ])
                                ->create([
                                    'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                                    'oem_said'       => null,
                                    'oem_amp_id'     => 'AMP-ID-123',
                                    'oem_sar_number' => 'SAR-123',
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

                            return $customer;
                        },
                    ],
                    'no types'       => [
                        new GraphQLSuccess(
                            'customer',
                            $customerEmptyContract,
                        ),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [
                                // empty
                            ],
                        ],
                        $customerContractFactory,
                    ],
                    'type not match' => [
                        new GraphQLSuccess(
                            'customer',
                            $customerEmptyContract,
                        ),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $customerContractFactory,
                    ],
                    'not allowed'    => [
                        new GraphQLSuccess('customer'),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [],
                        ],
                        static function (TestCase $test, Organization $organization): Customer {
                            $reseller = Reseller::factory()->create([
                                'id'              => $organization->getKey(),
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 1,
                                'assets_count'    => 0,
                            ]);
                            Document::factory()->for($reseller)->create();
                            $customer = Customer::factory()
                                ->create([
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 0,
                                    'quotes_count'    => 0,
                                    'contracts_count' => 0,
                                    'contacts_count'  => 0,
                                    'locations_count' => 0,
                                ]);

                            return $customer;
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
        $customerQuoteEmptyFactory = static function (TestCase $test, Organization $organization): Customer {
            $reseller = Reseller::factory()->create([
                'id'              => $organization->getKey(),
                'name'            => 'reseller1',
                'customers_count' => 0,
                'locations_count' => 1,
                'assets_count'    => 0,
                'contacts_count'  => 0,
                'changed_at'      => '2021-10-19 10:15:00',
                'synced_at'       => '2021-10-19 10:25:00',
            ]);
            $type     = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name aaa',
            ]);
            Document::factory()
                ->for($reseller)
                ->for($type)
                ->create();
            $customer = Customer::factory()
                ->create([
                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                    'name'            => 'name aaa',
                    'assets_count'    => 2,
                    'quotes_count'    => 2,
                    'contracts_count' => 2,
                    'contacts_count'  => 0,
                    'locations_count' => 1,
                    'changed_at'      => '2021-10-19 10:15:00',
                    'synced_at'       => '2021-10-19 10:25:00',
                ]);
            $customer->resellers()->attach($reseller, [
                'assets_count'    => 0,
                'quotes_count'    => 0,
                'contracts_count' => 0,
            ]);

            return $customer;
        };

        $customerQuoteFactory = static function (TestCase $test, Organization $organization, User $user): Customer {
            $user->save();
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
                'id'              => $organization->getKey(),
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
                    'quotes_count'    => 2,
                    'contracts_count' => 2,
                    'contacts_count'  => 1,
                    'locations_count' => 1,
                    'changed_at'      => '2021-10-19 10:15:00',
                    'synced_at'       => '2021-10-19 10:25:00',
                ]);
            $customer->resellers()->attach($reseller, [
                'assets_count'    => 0,
                'quotes_count'    => 0,
                'contracts_count' => 0,
            ]);

            CustomerLocation::factory()->create([
                'customer_id' => $customer,
                'location_id' => $location,
            ]);

            // OEM Creation belongs to
            $oem = Oem::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                'key'  => 'key',
                'name' => 'oem1',
            ]);
            // Type Creation belongs to
            $type = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name aaa',
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
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
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

            Document::factory()
                ->for($oem)
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
                ->hasEntries(1, [
                    'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                    'asset_id'         => Asset::factory()->create([
                        'id'          => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24988',
                        'reseller_id' => $reseller,
                    ]),
                    'serial_number'    => null,
                    'product_id'       => $product,
                    'service_group_id' => $serviceGroup,
                    'service_level_id' => $serviceLevel,
                    'list_price'       => 67.12,
                    'renewal'          => 24.20,
                    'start'            => '2021-01-01',
                    'end'              => '2024-01-01',
                ])
                ->hasContacts(1, [
                    'name'        => 'contact3',
                    'email'       => 'contact3@test.com',
                    'phone_valid' => false,
                ])
                ->create([
                    'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'oem_said'       => '225d982d-926d-3db4-ba0b-81ab17b790b0',
                    'oem_amp_id'     => 'AMP-ID-123',
                    'oem_sar_number' => 'SAR-123',
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

            return $customer;
        };
        $customerQuote        = [
            'quotes'           => [
                [
                    'id'             => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                    'oem_id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                    'customer_id'    => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                    'type_id'        => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                    'reseller_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                    'currency_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'language_id'    => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                    'distributor_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                    'is_contract'    => false,
                    'is_quote'       => true,
                    'number'         => '1323',
                    'price'          => 100,
                    'start'          => '2021-01-01',
                    'end'            => '2024-01-01',
                    'oem'            => [
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        'key'  => 'key',
                        'name' => 'oem1',
                    ],
                    'oem_said'       => '225d982d-926d-3db4-ba0b-81ab17b790b0',
                    'oem_amp_id'     => 'AMP-ID-123',
                    'oem_sar_number' => 'SAR-123',
                    'oemGroup'       => null,
                    'type'           => [
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        'name' => 'name aaa',
                    ],
                    'statuses_count' => 1,
                    'statuses'       => [
                        [
                            'id'   => '126042b6-2bc7-4009-9366-b4c95a94c73b',
                            'name' => 'status a',
                        ],
                    ],
                    'customer'       => [
                        'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        'name'            => 'name aaa',
                        'assets_count'    => 0,
                        'quotes_count'    => 0,
                        'contracts_count' => 0,
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
                    'reseller'       => [
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
                    'currency'       => [
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                        'name' => 'Currency1',
                        'code' => 'CUR',
                    ],
                    'entries_count'  => 2,
                    'entries'        => [
                        [
                            'id'               => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24989',
                            'service_level_id' => 'e2bb80fc-cedf-4ad2-b723-1e250805d2a0',
                            'document_id'      => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24981',
                            'list_price'       => 67.12,
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
                            'removed_at'       => null,
                        ],
                    ],
                    'language'       => [
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24991',
                        'name' => 'Lang1',
                        'code' => 'en',
                    ],
                    'contacts_count' => 3,
                    'contacts'       => [
                        [
                            'name'        => 'contact3',
                            'email'       => 'contact3@test.com',
                            'phone_valid' => false,
                        ],
                    ],
                    'distributor'    => [
                        'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24990',
                        'name' => 'distributor1',
                    ],
                    'assets_count'   => 1,
                    'changed_at'     => '2021-10-19T10:15:00+00:00',
                    'synced_at'      => '2021-10-19T10:25:00+00:00',
                ],
            ],
            'quotesAggregated' => [
                'count'            => 1,
                'groups'           => [
                    [
                        'key'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                        'count' => 1,
                    ],
                ],
                'groupsAggregated' => [
                    'count' => 1,
                ],
            ],
        ];
        $customerEmptyQuote   = [
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
                new OrgRootDataProvider('customer'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer'),
                        [],
                        static function (TestCase $test, Organization $organization): Customer {
                            Document::factory()->create();

                            return Customer::factory()->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgResellerDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok'                                        => [
                        new GraphQLSuccess(
                            'customer',
                            $customerQuote,
                        ),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $customerQuoteFactory,
                    ],
                    'not allowed'                               => [
                        new GraphQLSuccess('customer'),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [],
                        ],
                        static function (TestCase $test, Organization $organization): Customer {
                            $reseller = Reseller::factory()->create([
                                'id'              => $organization->getKey(),
                                'name'            => 'reseller1',
                                'customers_count' => 0,
                                'locations_count' => 1,
                                'assets_count'    => 0,
                            ]);
                            Document::factory()->for($reseller)->create();
                            $customer = Customer::factory()
                                ->create([
                                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                                    'name'            => 'name aaa',
                                    'assets_count'    => 0,
                                    'quotes_count'    => 0,
                                    'contracts_count' => 0,
                                    'contacts_count'  => 0,
                                    'locations_count' => 0,
                                ]);

                            return $customer;
                        },
                    ],
                    'no quote_types + contract_types not match' => [
                        new GraphQLSuccess(
                            'customer',
                            $customerQuote,
                        ),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [
                                'd4ad2f4f-7751-4cd2-a6be-71bcee84f37a',
                            ],
                            'ep.quote_types'              => [
                                // empty
                            ],
                        ],
                        $customerQuoteFactory,
                    ],
                    'no quote_types + contract_types match'     => [
                        new GraphQLSuccess(
                            'customer',
                            $customerEmptyQuote,
                        ),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        $customerQuoteEmptyFactory,
                    ],
                    'quote_types not match'                     => [
                        new GraphQLSuccess(
                            'customer',
                            $customerEmptyQuote,
                        ),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.quote_types'              => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac2498a',
                            ],
                        ],
                        $customerQuoteEmptyFactory,
                    ],
                    'no quote_types + no contract_types'        => [
                        new GraphQLSuccess(
                            'customer',
                            $customerEmptyQuote,
                        ),
                        [
                            'ep.document_statuses_hidden' => [],
                            'ep.contract_types'           => [],
                            'ep.quote_types'              => [],
                        ],
                        $customerQuoteEmptyFactory,
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryAssetAggregate(): array {
        $factory = static function (TestCase $test, Organization $organization): Customer {
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
                'id'              => $organization->getKey(),
                'name'            => 'reseller1',
                'customers_count' => 0,
                'locations_count' => 1,
                'assets_count'    => 0,
            ]);

            ResellerLocation::factory()->create([
                'reseller_id' => $reseller,
                'location_id' => $location,
            ]);

            $customer = Customer::factory()
                ->hasContacts(1, [
                    'name'        => 'contact1',
                    'email'       => 'contact1@test.com',
                    'phone_valid' => false,
                ])
                ->create([
                    'id'              => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20944',
                    'name'            => 'name aaa',
                    'assets_count'    => 0,
                    'quotes_count'    => 0,
                    'contracts_count' => 0,
                    'contacts_count'  => 1,
                    'locations_count' => 1,
                ]);

            $customer->resellers()->attach($reseller);

            CustomerLocation::factory()->create([
                'customer_id' => $customer,
                'location_id' => $location,
            ]);

            // Type
            $type  = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                'name' => 'name1',
                'key'  => 'key1',
            ]);
            $type2 = Type::factory()->create([
                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                'name' => 'name2',
                'key'  => 'key2',
            ]);
            // Assets
            Asset::factory()
                ->hasCoverages(1, [
                    'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                    'name' => 'name2',
                    'key'  => 'key2',
                ])
                ->create([
                    'type_id'         => $type,
                    'reseller_id'     => $reseller,
                    'customer_id'     => $customer,
                    'coverages_count' => 1,
                ]);
            Asset::factory()->create([
                'type_id'     => $type,
                'reseller_id' => $reseller,
                'customer_id' => $customer,
            ]);
            Asset::factory()->create([
                'type_id'     => $type2,
                'reseller_id' => $reseller,
                'customer_id' => $customer,
            ]);
            // Another customer
            $customer2 = Customer::factory()->create();
            Asset::factory()->create([
                'type_id'     => $type2,
                'customer_id' => $customer2,
            ]);

            return $customer;
        };
        $params  = [
            'where' => [
                'type_id' => [
                    'in' => [
                        'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                    ],
                ],
            ],
        ];

        return (new CompositeDataProvider(
            new AuthOrgResellerDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
            new OrgUserDataProvider('customer', [
                'customers-view',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'customer',
                        [
                            'assetsAggregated' => [
                                'count'            => 3,
                                'groups'           => [
                                    [
                                        'key'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                        'count' => 2,
                                    ],
                                    [
                                        'key'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                        'count' => 1,

                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 2,
                                ],
                                'types'            => [
                                    [
                                        'count'   => 2,
                                        'type_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                        'type'    => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                            'name' => 'name1',
                                            'key'  => 'key1',
                                        ],
                                    ],
                                    [
                                        'count'   => 1,
                                        'type_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                        'type'    => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24986',
                                            'name' => 'name2',
                                            'key'  => 'key2',
                                        ],
                                    ],
                                ],
                                'coverages'        => [
                                    [
                                        'count'       => 1,
                                        'coverage_id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                        'coverage'    => [
                                            'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987',
                                            'name' => 'name2',
                                            'key'  => 'key2',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ),
                    $factory,
                    $params,
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryContractsAggregate(): array {
        $hiddenStatus = 'fc52c5b5-cbfc-4481-872a-ad35035f5890';
        $type         = '184a9fb0-ba79-47fa-8a19-9f712876f16b';
        $factory      = static function (
            TestCase $test,
            Organization $organization,
        ) use (
            $type,
            $hiddenStatus,
        ): Customer {
            $reseller = Reseller::factory()->create([
                'id' => $organization->getKey(),
            ]);
            $customer = Customer::factory()->create();

            $customer->resellers()->attach($reseller);

            $currencyA = Currency::factory()->create([
                'id'   => '6bfadfe5-a886-4c7d-ac56-a8ac215aea00',
                'code' => 'EUR',
                'name' => 'EUR',
            ]);
            $currencyB = Currency::factory()->create([
                'id'   => 'bb22eb9c-536a-4a93-97c6-28ee77cea438',
                'code' => 'USD',
                'name' => 'USD',
            ]);
            $currencyC = Currency::factory()->create([
                'id'   => '7a83e827-2b8c-4e6f-9905-7ca1ca5b6364',
                'code' => 'UNK',
                'name' => 'UNK',
            ]);
            $type      = Type::factory()->create([
                'id' => $type,
            ]);

            Document::factory()->create([
                'reseller_id' => $reseller,
                'customer_id' => $customer,
                'currency_id' => $currencyA,
                'type_id'     => $type,
                'price'       => '123.45',
            ]);
            Document::factory()->create([
                'reseller_id' => $reseller,
                'customer_id' => $customer,
                'currency_id' => $currencyA,
                'type_id'     => $type,
                'price'       => '123.45',
            ]);
            Document::factory()->create([
                'reseller_id' => $reseller,
                'customer_id' => $customer,
                'currency_id' => $currencyB,
                'type_id'     => $type,
                'price'       => '123.45',
            ]);
            Document::factory()->create([
                'reseller_id' => $reseller,
                'customer_id' => $customer,
                'currency_id' => $currencyC,
                'type_id'     => $type,
                'price'       => '10.00',
            ]);

            // Another customer
            Document::factory()->create([
                'reseller_id' => $reseller,
                'currency_id' => $currencyA,
                'type_id'     => $type,
                'price'       => '543.21',
            ]);

            // Hidden Status
            Document::factory()
                ->hasStatuses(1, [
                    'id' => $hiddenStatus,
                ])
                ->create([
                    'reseller_id' => $reseller,
                    'customer_id' => $customer,
                    'currency_id' => $currencyA,
                    'type_id'     => $type,
                    'price'       => 5,
                ]);

            // Return
            return $customer;
        };
        $params       = [
            'where' => [
                'anyOf' => [
                    [
                        'currency_id' => [
                            'notEqual' => '7a83e827-2b8c-4e6f-9905-7ca1ca5b6364',
                        ],
                    ],
                    [
                        'currency_id' => [
                            'isNull' => 'yes',
                        ],
                    ],
                ],
            ],
        ];

        return (new CompositeDataProvider(
            new AuthOrgResellerDataProvider('customer', 'f9834bc1-2f2f-4c57-bb8d-7a224ac24987'),
            new OrgUserDataProvider('customer', [
                'customers-view',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess(
                        'customer',
                        [
                            'contractsAggregated' => [
                                'count'            => 3,
                                'groups'           => [
                                    [
                                        'key'   => '6bfadfe5-a886-4c7d-ac56-a8ac215aea00',
                                        'count' => 2,
                                    ],
                                    [
                                        'key'   => 'bb22eb9c-536a-4a93-97c6-28ee77cea438',
                                        'count' => 1,
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 2,
                                ],
                                'prices'           => [
                                    [
                                        'count'       => 2,
                                        'amount'      => 246.9,
                                        'currency_id' => '6bfadfe5-a886-4c7d-ac56-a8ac215aea00',
                                        'currency'    => [
                                            'id'   => '6bfadfe5-a886-4c7d-ac56-a8ac215aea00',
                                            'name' => 'EUR',
                                            'code' => 'EUR',
                                        ],
                                    ],
                                    [
                                        'count'       => 1,
                                        'amount'      => 123.45,
                                        'currency_id' => 'bb22eb9c-536a-4a93-97c6-28ee77cea438',
                                        'currency'    => [
                                            'id'   => 'bb22eb9c-536a-4a93-97c6-28ee77cea438',
                                            'name' => 'USD',
                                            'code' => 'USD',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ),
                    [
                        'ep.document_statuses_hidden' => [
                            $hiddenStatus,
                        ],
                        'ep.contract_types'           => $type,
                    ],
                    $factory,
                    $params,
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryChangeRequests(): array {
        return (new MergeDataProvider([
            'root'         => new CompositeDataProvider(
                new OrgRootDataProvider('customer'),
                new OrgUserDataProvider('customer', [
                    'customers-view',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer'),
                        static function (TestCase $test, Organization $org): Customer {
                            return Customer::factory()->ownedBy($org)->create();
                        },
                    ],
                ]),
            ),
            'organization' => new CompositeDataProvider(
                new AuthOrgDataProvider('customer'),
                new OrgUserDataProvider(
                    'customer',
                    [
                        'customers-view',
                    ],
                    '22ca602c-ae8c-41b0-83a0-c6a5e7cf3538',
                ),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('customer', [
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
                        static function (TestCase $test, Organization $org, User $user): Customer {
                            $organization = Organization::factory()->create();
                            $customer     = Customer::factory()->ownedBy($org)->create();

                            ChangeRequest::factory()
                                ->ownedBy($organization)
                                ->for($user)
                                ->create([
                                    'id'          => '681efbcf-0602-4356-ae3e-426d157ca489',
                                    'object_id'   => $customer->getKey(),
                                    'object_type' => $customer->getMorphClass(),
                                ]);
                            ChangeRequest::factory()
                                ->ownedBy($org)
                                ->for($user)
                                ->hasFiles(1, [
                                    'name' => 'documents.csv',
                                ])
                                ->create([
                                    'id'          => '4acb3b3a-82b4-4ae4-8413-cb87c0fed513',
                                    'object_id'   => $customer->getKey(),
                                    'object_type' => $customer->getMorphClass(),
                                    'message'     => 'Change Request A',
                                    'subject'     => 'Subject A',
                                    'from'        => 'user@example.com',
                                    'to'          => ['test@example.com'],
                                    'cc'          => ['cc@example.com'],
                                    'bcc'         => ['bcc@example.com'],
                                ]);

                            return $customer;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
