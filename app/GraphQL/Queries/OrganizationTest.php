<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Types\Audit;
use App\Models\Currency;
use App\Models\Kpi;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Role;
use App\Models\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\GraphQL\JsonFragmentPaginatedSchema;
use Tests\TestCase;

use function json_encode;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Organization
 */
class OrganizationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @coversNothing
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
        Closure $prepare = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $this->setSettings($settings);

        $organizationId = 'wrong';
        if ($prepare) {
            $organizationId = $prepare($this, $organization, $user)->getKey();
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
                        locations {
                          id
                          state
                          postcode
                          line_one
                          line_two
                          latitude
                          longitude
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
                          welcome_heading
                          welcome_underline
                          dashboard_image_url
                        }
                        statuses {
                            id
                            key
                            name
                        }
                        contacts {
                          name
                          email
                          phone_valid
                        }
                        headquarter {
                          id
                          state
                          postcode
                          line_one
                          line_two
                          latitude
                          longitude
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
     */
    public function testUsers(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this, $organization, $user);
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query organization($id: ID!) {
                    organization(id: $id) {
                        users {
                            data {
                                id
                                email
                                given_name
                                family_name
                                email_verified
                                role {
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
                }
                GRAPHQL,
                [
                    'id' => $organization?->getKey() ?: $this->faker->uuid,
                ],
            )
            ->assertThat($expected);
    }

    /**
     * @covers ::roles
     *
     * @dataProvider dataProviderRoles
     */
    public function testRoles(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this, $organization);
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
                    'id' => $organization?->getKey() ?: $this->faker->uuid,
                ],
            )
            ->assertThat($expected);
    }

    /**
     * @covers       \App\GraphQL\Queries\AuditContext::__invoke
     *
     * @dataProvider dataProviderAudits
     */
    public function testAudits(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $prepare = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        $organizationId = 'wrong';
        if ($prepare) {
            $organizationId = $prepare($this, $organization, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query organization($id: ID!){
                    organization(id: $id) {
                        audits {
                            data {
                                id
                                organization_id
                                user_id
                                object_type
                                object_id
                                context
                                action
                                created_at
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
            new RootOrganizationDataProvider('organization'),
            new OrganizationUserDataProvider('organization', [
                'administer',
            ]),
            new ArrayDataProvider([
                'ok' => [
                    new GraphQLSuccess('organization', Org::class, [
                        'id'             => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                        'name'           => 'org1',
                        'root'           => false,
                        'locale'         => 'en',
                        'website_url'    => 'https://www.example.com',
                        'email'          => 'test@example.com',
                        'analytics_code' => 'analytics_code',
                        'currency_id'    => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                        'timezone'       => 'Europe/London',
                        'currency'       => [
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'name' => 'currency1',
                            'code' => 'CUR',
                        ],
                        'locations'      => [
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
                        'contacts'       => [
                            [
                                'name'        => 'contact1',
                                'email'       => 'contact1@test.com',
                                'phone_valid' => false,
                            ],
                        ],
                        'headquarter'    => [
                            'id'        => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                            'state'     => 'state1',
                            'postcode'  => '19911',
                            'line_one'  => 'line_one_data',
                            'line_two'  => 'line_two_data',
                            'latitude'  => 47.91634204,
                            'longitude' => -2.26318359,
                        ],
                        'branding'       => [
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
                            'welcome_heading'         => 'heading',
                            'welcome_underline'       => 'underline',
                        ],
                        'statuses'       => [
                            [
                                'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                'key'  => 'active',
                                'name' => 'active',
                            ],
                        ],
                        'kpi'            => [
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
                        'ep.headquarter_type' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                    ],
                    static function (TestCase $test, ?Organization $organization, ?User $user): Organization {
                        $currency = Currency::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'name' => 'currency1',
                            'code' => 'CUR',
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
                                'id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                            ]);
                        Location::factory()
                            ->hasTypes(1, [
                                'id'   => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                                'name' => 'headquarter',
                            ])
                            ->create([
                                'id'          => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20944',
                                'state'       => 'state1',
                                'postcode'    => '19911',
                                'line_one'    => 'line_one_data',
                                'line_two'    => 'line_two_data',
                                'latitude'    => '47.91634204',
                                'longitude'   => '-2.26318359',
                                'object_type' => $reseller->getMorphClass(),
                                'object_id'   => $reseller->getKey(),
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
                                'branding_welcome_heading'         => 'heading',
                                'branding_welcome_underline'       => 'underline',
                                'timezone'                         => 'Europe/London',
                                'keycloak_group_id'                => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20945',
                            ]);

                        Kpi::factory()->create([
                            'object_id'                           => $organization->getKey(),
                            'object_type'                         => (new Reseller())->getMorphClass(),
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
                new RootOrganizationDataProvider('organization'),
                new OrganizationUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess(
                            'organization',
                            new JsonFragmentPaginatedSchema('users', self::class, [
                                [
                                    'id'             => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'email'          => 'example@test.com',
                                    'email_verified' => true,
                                    'given_name'     => 'first',
                                    'family_name'    => 'last',
                                ],
                            ]),
                        ),
                        static function (TestCase $test, Organization $organization): void {
                            $organization->keycloak_group_id = 'f9396bc1-2f2f-4c58-2f2f-7a224ac20945';
                            Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);
                            $organization->save();

                            User::factory()
                                ->hasOrganizationUser(1, [
                                    'organization_id' => $organization->getKey(),
                                ])
                                ->create([
                                    'id'             => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'email'          => 'example@test.com',
                                    'email_verified' => true,
                                    'given_name'     => 'first',
                                    'family_name'    => 'last',
                                ]);
                        },
                    ],
                ]),
            ),
            'org-administer' => new CompositeDataProvider(
                new RootOrganizationDataProvider('organization'),
                new OrganizationUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess(
                            'organization',
                            new JsonFragmentPaginatedSchema('users', self::class, [
                                [
                                    'id'             => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'email'          => 'example@test.com',
                                    'email_verified' => true,
                                    'given_name'     => 'first',
                                    'family_name'    => 'last',
                                ],
                            ]),
                        ),
                        static function (TestCase $test, Organization $organization): void {
                            $organization->keycloak_group_id = 'f9396bc1-2f2f-4c58-2f2f-7a224ac20945';
                            Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);
                            $organization->save();

                            User::factory()
                                ->hasOrganizationUser(1, [
                                    'organization_id' => $organization->getKey(),
                                ])
                                ->create([
                                    'id'             => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'email'          => 'example@test.com',
                                    'email_verified' => true,
                                    'given_name'     => 'first',
                                    'family_name'    => 'last',
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
    public function dataProviderRoles(): array {
        return (new MergeDataProvider([
            'administer'     => new CompositeDataProvider(
                new RootOrganizationDataProvider('organization'),
                new OrganizationUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', self::class, new JsonFragment('roles', [
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
                new RootOrganizationDataProvider('organization'),
                new OrganizationUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', self::class, new JsonFragment('roles', [
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
                new RootOrganizationDataProvider('organization'),
                new OrganizationUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', new JsonFragmentPaginatedSchema('audits', Audit::class), [
                            'audits' => [
                                'data'          => [
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
                                        'action'          => 'action1',
                                        'created_at'      => '2021-01-01T00:00:00+00:00',
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
                                    'action'      => 'action1',
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
                new RootOrganizationDataProvider('organization'),
                new OrganizationUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', new JsonFragmentPaginatedSchema('audits', Audit::class), [
                            'audits' => [
                                'data'          => [
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
                                        'action'          => 'action1',
                                        'created_at'      => '2021-01-01T00:00:00+00:00',
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
                                    'action'      => 'action1',
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
