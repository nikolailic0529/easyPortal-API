<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\Currency;
use App\Models\Kpi;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\Enums\Action;
use App\Services\I18n\Eloquent\TranslatedString;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgRootDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Organization
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class OrganizationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @coversNothing
     *
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param SettingsFactory     $settingsFactory
     */
    public function testQuery(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $this->setSettings($settingsFactory);

        $organizationId = 'wrong';
        if ($prepare) {
            $organizationId = $prepare($this, $org, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query organization($id: ID!){
                    organization(id: $id) {
                        id
                        name
                        email
                        root
                        locale
                        website_url
                        analytics_code
                        timezone
                        currency_id
                        currency {
                            id
                            name
                            code
                        }
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
                        branding {
                            dark_theme
                            main_color
                            secondary_color
                            logo_url
                            favicon_url
                            default_main_color
                            default_secondary_color
                            default_logo_url
                            default_favicon_url
                            welcome_image_url
                            welcome_heading {
                                locale
                                text
                            }
                            welcome_underline {
                                locale
                                text
                            }
                            dashboard_image_url
                        }
                        statuses {
                            id
                            key
                            name
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
                    }
                }
            ', ['id' => $organizationId])->assertThat($expected);
    }

    /**
     * @covers ::users
     *
     * @dataProvider dataProviderUsers
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testUsers(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $id   = $org?->getKey();

        if ($prepare) {
            $id = $prepare($this, $org, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query organization($id: ID!) {
                    organization(id: $id) {
                        users {
                            id
                            email
                            given_name
                            family_name
                            email_verified
                        }
                        usersAggregated {
                            count
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $id ?: $this->faker->uuid(),
                ],
            )
            ->assertThat($expected);
    }

    /**
     * @covers ::roles
     *
     * @dataProvider dataProviderRoles
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testRoles(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org = $this->setOrganization($orgFactory);

        $this->setUser($userFactory, $org);

        if ($prepare) {
            $prepare($this, $org);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query organization($id: ID!) {
                    organization(id: $id) {
                        roles {
                            id
                            name
                            permissions {
                                id
                                name
                                key
                                description
                            }
                        }
                    }
                }
                GRAPHQL,
                [
                    'id' => $org?->getKey() ?: $this->faker->uuid(),
                ],
            )
            ->assertThat($expected);
    }

    /**
     * @covers       \App\GraphQL\Queries\Administration\AuditContext::__invoke
     *
     * @dataProvider dataProviderAudits
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     */
    public function testAudits(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);

        $organizationId = 'wrong';
        if ($prepare) {
            $organizationId = $prepare($this, $org, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query organization($id: ID!){
                    organization(id: $id) {
                        audits {
                            id
                            organization_id
                            user_id
                            object_type
                            object_id
                            context
                            action
                            created_at
                        }
                        auditsAggregated {
                            count
                        }
                    }
                }
            ', ['id' => $organizationId])->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new AuthOrgRootDataProvider('organization'),
            new OrgUserDataProvider('organization', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('organization', [
                        'id'              => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        'name'            => 'org1',
                        'root'            => false,
                        'locale'          => 'en',
                        'website_url'     => 'https://www.example.com',
                        'email'           => 'test@example.com',
                        'analytics_code'  => 'analytics_code',
                        'currency_id'     => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                        'timezone'        => 'Europe/London',
                        'currency'        => [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'name' => 'currency1',
                            'code' => 'CUR',
                        ],
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
                        'branding'        => [
                            'dark_theme'              => true,
                            'main_color'              => '#00000F',
                            'secondary_color'         => '#0000F0',
                            'logo_url'                => 'https://www.example.com/logo.png',
                            'favicon_url'             => 'https://www.example.com/favicon.png',
                            'default_main_color'      => '#000F00',
                            'default_secondary_color' => '#00F000',
                            'default_logo_url'        => 'https://www.example.com/logo-default.png',
                            'default_favicon_url'     => 'https://www.example.com/favicon-default.png',
                            'welcome_image_url'       => 'https://www.example.com/welcome-image.png',
                            'dashboard_image_url'     => 'https://www.example.com/dashboard-image.png',
                            'welcome_heading'         => [
                                [
                                    'locale' => 'en_GB',
                                    'text'   => 'heading',
                                ],
                            ],
                            'welcome_underline'       => [
                                [
                                    'locale' => 'en_GB',
                                    'text'   => 'underline',
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
                        ],
                    ]),
                    [
                        'ep.headquarter_type' => [
                            'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        ],
                    ],
                    static function (TestCase $test, ?Organization $organization, ?User $user): Organization {
                        $kpi      = Kpi::factory()->create([
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
                        $currency = Currency::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'name' => 'currency1',
                            'code' => 'CUR',
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
                        $reseller = Reseller::factory()
                            ->hasContacts(1, [
                                'name'        => 'contact1',
                                'email'       => 'contact1@test.com',
                                'phone_valid' => false,
                            ])
                            ->hasStatuses(1, [
                                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                'name'        => 'active',
                                'key'         => 'active',
                                'object_type' => (new Reseller())->getMorphClass(),
                            ])
                            ->create([
                                'id'     => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'kpi_id' => $kpi,
                            ]);

                        ResellerLocation::factory()
                            ->hasTypes(1, [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'headquarter',
                            ])
                            ->create([
                                'reseller_id' => $reseller,
                                'location_id' => $location,
                            ]);

                        $organization = Organization::factory()
                            ->for($currency)
                            ->hasRoles(1, [
                                'id'   => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20946',
                                'name' => 'role1',
                            ])
                            ->create([
                                'id'                               => $reseller->getKey(),
                                'name'                             => 'org1',
                                'locale'                           => 'en',
                                'website_url'                      => 'https://www.example.com',
                                'email'                            => 'test@example.com',
                                'analytics_code'                   => 'analytics_code',
                                'branding_dark_theme'              => true,
                                'branding_main_color'              => '#00000F',
                                'branding_secondary_color'         => '#0000F0',
                                'branding_logo_url'                => 'https://www.example.com/logo.png',
                                'branding_favicon_url'             => 'https://www.example.com/favicon.png',
                                'branding_default_main_color'      => '#000F00',
                                'branding_default_secondary_color' => '#00F000',
                                'branding_default_logo_url'        => 'https://www.example.com/logo-default.png',
                                'branding_default_favicon_url'     => 'https://www.example.com/favicon-default.png',
                                'branding_welcome_image_url'       => 'https://www.example.com/welcome-image.png',
                                'branding_dashboard_image_url'     => 'https://www.example.com/dashboard-image.png',
                                'branding_welcome_heading'         => new TranslatedString([
                                    'en_GB' => 'heading',
                                ]),
                                'branding_welcome_underline'       => new TranslatedString([
                                    'en_GB' => 'underline',
                                ]),
                                'timezone'                         => 'Europe/London',
                                'keycloak_group_id'                => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20945',
                            ]);

                        return $organization;
                    },
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderUsers(): array {
        return (new MergeDataProvider([
            'administer'     => new CompositeDataProvider(
                new AuthOrgRootDataProvider('organization'),
                new OrgUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', [
                            'users'           => [
                                [
                                    'id'             => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'email'          => 'example@test.com',
                                    'email_verified' => true,
                                    'given_name'     => 'first',
                                    'family_name'    => 'last',
                                ],
                            ],
                            'usersAggregated' => [
                                'count' => 1,
                            ],
                        ]),
                        static function (): Organization {
                            $organization = Organization::factory()->create();

                            Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);

                            User::factory()
                                ->hasOrganizations(1, [
                                    'organization_id' => $organization->getKey(),
                                ])
                                ->create([
                                    'id'             => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'email'          => 'example@test.com',
                                    'email_verified' => true,
                                    'given_name'     => 'first',
                                    'family_name'    => 'last',
                                ]);

                            return $organization;
                        },
                    ],
                ]),
            ),
            'org-administer' => new CompositeDataProvider(
                new AuthOrgRootDataProvider('organization'),
                new OrgUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', [
                            'users'           => [
                                [
                                    'id'             => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'email'          => 'example@test.com',
                                    'email_verified' => true,
                                    'given_name'     => 'first',
                                    'family_name'    => 'last',
                                ],
                            ],
                            'usersAggregated' => [
                                'count' => 1,
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization): Organization {
                            $organization = Organization::factory()->create();

                            Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);

                            User::factory()
                                ->hasOrganizations(1, [
                                    'organization_id' => $organization->getKey(),
                                ])
                                ->create([
                                    'id'             => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'email'          => 'example@test.com',
                                    'email_verified' => true,
                                    'given_name'     => 'first',
                                    'family_name'    => 'last',
                                ]);

                            return $organization;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderRoles(): array {
        return (new MergeDataProvider([
            'administer'     => new CompositeDataProvider(
                new AuthOrgRootDataProvider('organization'),
                new OrgUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', new JsonFragment('roles', [
                            [
                                'id'          => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                'name'        => 'role1',
                                'permissions' => [
                                    [
                                        'id'          => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                        'key'         => 'permission1',
                                        'name'        => 'permission1',
                                        'description' => 'permission1',
                                    ],
                                ],
                            ],
                        ])),
                        static function (TestCase $test, Organization $organization): void {
                            if (!$organization->keycloak_group_id) {
                                $organization->keycloak_group_id = $test->faker->uuid();
                                $organization->save();
                            }

                            Role::factory()
                                ->hasPermissions(1, [
                                    'id'  => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                    'key' => 'permission1',
                                ])
                                ->create([
                                    'id'              => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'name'            => 'role1',
                                    'organization_id' => $organization->getKey(),
                                ]);
                        },
                    ],
                ]),
            ),
            'org-administer' => new CompositeDataProvider(
                new AuthOrgRootDataProvider('organization'),
                new OrgUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', new JsonFragment('roles', [
                            [
                                'id'          => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                'name'        => 'role1',
                                'permissions' => [
                                    [
                                        'id'          => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                        'key'         => 'permission1',
                                        'name'        => 'permission1',
                                        'description' => 'permission1',
                                    ],
                                ],
                            ],
                        ])),
                        static function (TestCase $test, Organization $organization): void {
                            if (!$organization->keycloak_group_id) {
                                $organization->keycloak_group_id = $test->faker->uuid();
                                $organization->save();
                            }
                            Role::factory()
                                ->hasPermissions(1, [
                                    'id'  => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                    'key' => 'permission1',
                                ])
                                ->create([
                                    'id'              => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'name'            => 'role1',
                                    'organization_id' => $organization->getKey(),
                                ]);
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderAudits(): array {
        return (new MergeDataProvider([
            'administer'     => new CompositeDataProvider(
                new AuthOrgRootDataProvider('organization'),
                new OrgUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', [
                            'audits'           => [
                                [
                                    'id'              => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20947',
                                    'object_type'     => 'User',
                                    'object_id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                                    'user_id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e02',
                                    'organization_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                    'context'         => json_encode([
                                        'properties' => [
                                            'email'    => [
                                                'value'    => 'test@gmail.com',
                                                'pervious' => null,
                                            ],
                                            'password' => [
                                                'value'    => 'pass',
                                                'pervious' => null,
                                            ],
                                        ],
                                    ]),
                                    'action'          => Action::authFailed(),
                                    'created_at'      => '2021-01-01T00:00:00+00:00',
                                ],
                            ],
                            'auditsAggregated' => [
                                'count' => 1,
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization): Organization {
                            $user         = User::factory()->create([
                                'id' => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                            ]);
                            $organization = Organization::factory()
                                ->hasAudits(1, [
                                    'id'          => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20947',
                                    'object_type' => $user->getMorphClass(),
                                    'object_id'   => $user->getKey(),
                                    'user_id'     => '439a0a06-d98a-41f0-b8e5-4e5722518e02',
                                    'context'     => [
                                        'properties' => [
                                            'email'    => [
                                                'value'    => 'test@gmail.com',
                                                'pervious' => null,
                                            ],
                                            'password' => [
                                                'value'    => 'pass',
                                                'pervious' => null,
                                            ],
                                        ],
                                    ],
                                    'action'      => Action::authFailed(),
                                    'created_at'  => '2021-01-01 00:00:00',
                                ])
                                ->create([
                                    'id' => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                ]);
                            Organization::factory()->hasAudits(1)->create();

                            return $organization;
                        },
                    ],
                ]),
            ),
            'org-administer' => new CompositeDataProvider(
                new AuthOrgRootDataProvider('organization'),
                new OrgUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', [
                            'audits'           => [
                                [
                                    'id'              => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                                    'object_type'     => 'User',
                                    'object_id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                                    'user_id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e02',
                                    'organization_id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                    'context'         => json_encode([
                                        'properties' => [
                                            'email' => [
                                                'value'    => 'test@gmail.com',
                                                'pervious' => null,
                                            ],
                                        ],
                                    ]),
                                    'action'          => Action::exported(),
                                    'created_at'      => '2021-01-01T00:00:00+00:00',
                                ],
                            ],
                            'auditsAggregated' => [
                                'count' => 1,
                            ],
                        ]),
                        static function (TestCase $test, Organization $organization): Organization {
                            $user         = User::factory()->create([
                                'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                                'password' => 'pass',
                            ]);
                            $organization = Organization::factory()
                                ->hasAudits(1, [
                                    'id'          => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                                    'object_type' => $user->getMorphClass(),
                                    'object_id'   => $user->getKey(),
                                    'user_id'     => '439a0a06-d98a-41f0-b8e5-4e5722518e02',
                                    'context'     => [
                                        'properties' => [
                                            'email' => [
                                                'value'    => 'test@gmail.com',
                                                'pervious' => null,
                                            ],
                                        ],
                                    ],
                                    'action'      => Action::exported(),
                                    'created_at'  => '2021-01-01 00:00:00',
                                ])
                                ->create([
                                    'id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                ]);
                            Organization::factory()->hasAudits(1)->create();

                            return $organization;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
