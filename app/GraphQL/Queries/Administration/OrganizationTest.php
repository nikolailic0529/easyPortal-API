<?php declare(strict_types = 1);

namespace App\GraphQL\Queries\Administration;

use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\Data\Currency;
use App\Models\Enums\OrganizationType;
use App\Models\Kpi;
use App\Models\Organization;
use App\Models\Reseller;
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
use Tests\WithUser;

use function array_merge;
use function json_encode;

/**
 * @internal
 * @covers \App\GraphQL\Queries\Organization
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 */
class OrganizationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @coversNothing
     *
     * @dataProvider dataProviderQuery
     *
     * @param OrganizationFactory                                      $orgFactory
     * @param UserFactory                                              $userFactory
     * @param Closure(static, ?Organization, ?User): Organization|null $prepare
     */
    public function testQuery(
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
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query organization($id: ID!){
                    organization(id: $id) {
                        id
                        type
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
                        company {
                            id
                            name
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
                }
                GRAPHQL,
                [
                    'id' => $organizationId,
                ],
            )->assertThat($expected);
    }

    /**
     * @coversNothing
     *
     * @dataProvider dataProviderUsers
     *
     * @param OrganizationFactory                                      $orgFactory
     * @param UserFactory                                              $userFactory
     * @param Closure(static, ?Organization, ?User): Organization|null $prepare
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
                            groups(groupBy: {family_name: asc}) {
                                key
                                count
                            }
                            groupsAggregated(groupBy: {family_name: asc}) {
                                count
                            }
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
     * @coversNothing
     *
     * @dataProvider dataProviderRoles
     *
     * @param OrganizationFactory                                      $orgFactory
     * @param UserFactory                                              $userFactory
     * @param Closure(static, ?Organization, ?User): Organization|null $prepare
     */
    public function testRoles(
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
                    'id' => $id ?: $this->faker->uuid(),
                ],
            )
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderAudits
     *
     * @param OrganizationFactory                                      $orgFactory
     * @param UserFactory                                              $userFactory
     * @param Closure(static, ?Organization, ?User): Organization|null $prepare
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
        $id   = 'wrong';

        if ($prepare) {
            $id = $prepare($this, $org, $user)->getKey();
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                query organization($id: ID!){
                    organization(id: $id) {
                        audits {
                            id
                            organization_id
                            user_id
                            object_type
                            object_id
                            object {
                                ... on ChangeRequest {
                                    id
                                    __typename
                                }
                                ... on QuoteRequest {
                                    id
                                    __typename
                                }
                                ... on Organization {
                                    id
                                    __typename
                                }
                                ... on Invitation {
                                    id
                                    __typename
                                }
                                ... on User {
                                    id
                                    __typename
                                }
                                ... on Role {
                                    id
                                    __typename
                                }
                                ... on Unknown {
                                    id
                                    type
                                    __typename
                                }
                            }
                            context
                            action
                            created_at
                        }
                        auditsAggregated {
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
                GRAPHQL,
                [
                    'id' => $id,
                ],
            )
            ->assertThat($expected);
    }

    /**
     * @dataProvider dataProviderQueryChangeRequests
     *
     * @param OrganizationFactory                                  $orgFactory
     * @param UserFactory                                          $userFactory
     * @param Closure(static, ?Organization, ?User): Customer|null $organizationFactory
     */
    public function testQueryChangeRequests(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        Closure $organizationFactory = null,
    ): void {
        // Prepare
        $org  = $this->setOrganization($orgFactory);
        $user = $this->setUser($userFactory, $org);
        $id   = $organizationFactory
            ? $organizationFactory($this, $org, $user)->getKey()
            : $this->faker->uuid();

        // Test
        $this
            ->graphQL(/** @lang GraphQL */ '
                query organization($id: ID!) {
                    organization(id: $id) {
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
            ', ['id' => $id])
            ->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        $expected = [
            'id'             => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
            'name'           => 'org',
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
            'company'        => [
                'id'         => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                'name'       => 'org',
                'statuses'   => [
                    [
                        'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                        'key'  => 'active',
                        'name' => 'active',
                    ],
                ],
                'kpi'        => [
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
                'changed_at' => '2021-10-19T10:15:00+00:00',
                'synced_at'  => '2021-10-19T10:25:00+00:00',
            ],
        ];

        /**
         * @param Closure(Kpi): Reseller $companyFactory
         */
        $factory = static function (Closure $companyFactory): Closure {
            return static function () use ($companyFactory): Organization {
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
                $company  = $companyFactory($kpi);

                $organization = Organization::factory()
                    ->for($currency)
                    ->hasRoles(1, [
                        'id'   => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20946',
                        'name' => 'role1',
                    ])
                    ->create([
                        'id'                               => $company,
                        'type'                             => OrganizationType::get($company->getMorphClass()),
                        'name'                             => $company->name,
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
            };
        };

        return (new CompositeDataProvider(
            new AuthOrgRootDataProvider('organization'),
            new OrgUserDataProvider('organization', [
                'administer',
            ]),
            new ArrayDataProvider([
                'reseller' => [
                    new GraphQLSuccess('organization', array_merge($expected, [
                        'type' => OrganizationType::reseller(),
                    ])),
                    $factory(static function (Kpi $kpi): Reseller {
                        $reseller = Reseller::factory()
                            ->hasStatuses(1, [
                                'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                                'name'        => 'active',
                                'key'         => 'active',
                                'object_type' => (new Reseller())->getMorphClass(),
                            ])
                            ->create([
                                'id'              => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
                                'name'            => 'org',
                                'kpi_id'          => $kpi,
                                'changed_at'      => '2021-10-19 10:15:00',
                                'synced_at'       => '2021-10-19 10:25:00',
                                'contacts_count'  => 1,
                                'locations_count' => 1,
                            ]);

                        return $reseller;
                    }),
                ],
            ]),
        ))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderUsers(): array {
        return (new MergeDataProvider([
            'administer' => new CompositeDataProvider(
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
                                'count'            => 1,
                                'groups'           => [
                                    [
                                        'key'   => 'last',
                                        'count' => 1,
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 1,
                                ],
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
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderRoles(): array {
        return (new MergeDataProvider([
            'administer' => new CompositeDataProvider(
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
                        static function (TestCase $test, Organization $org): Organization {
                            if (!$org->keycloak_group_id) {
                                $org->keycloak_group_id = $test->faker->uuid();
                                $org->save();
                            }

                            Role::factory()
                                ->hasPermissions(1, [
                                    'id'  => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                    'key' => 'permission1',
                                ])
                                ->create([
                                    'id'              => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                    'name'            => 'role1',
                                    'organization_id' => $org->getKey(),
                                ]);

                            return $org;
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
            'administer' => new CompositeDataProvider(
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
                                    'object'          => [
                                        'id'         => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                                        '__typename' => 'User',
                                    ],
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
                                    'action'          => 'AuthFailed',
                                    'created_at'      => '2021-01-01T00:00:00+00:00',
                                ],
                            ],
                            'auditsAggregated' => [
                                'count'            => 1,
                                'groups'           => [
                                    [
                                        'key'   => '439a0a06-d98a-41f0-b8e5-4e5722518e02',
                                        'count' => 1,
                                    ],
                                ],
                                'groupsAggregated' => [
                                    'count' => 1,
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
        ]))->getData();
    }

    /**
     * @return array<mixed>
     */
    public function dataProviderQueryChangeRequests(): array {
        return (new MergeDataProvider([
            'administer' => new CompositeDataProvider(
                new AuthOrgRootDataProvider('organization'),
                new OrgUserDataProvider(
                    'organization',
                    [
                        'administer',
                    ],
                    '22ca602c-ae8c-41b0-83a0-c6a5e7cf3538',
                ),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', new JsonFragment('changeRequests', [
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
                        ])),
                        static function (TestCase $test, Organization $org, User $user): Organization {
                            $organization = Organization::factory()->create();

                            ChangeRequest::factory()
                                ->ownedBy($org)
                                ->for($user)
                                ->hasFiles(1, [
                                    'name' => 'documents.csv',
                                ])
                                ->create([
                                    'id'          => '4acb3b3a-82b4-4ae4-8413-cb87c0fed513',
                                    'object_id'   => $organization->getKey(),
                                    'object_type' => $organization->getMorphClass(),
                                    'message'     => 'Change Request A',
                                    'subject'     => 'Subject A',
                                    'from'        => 'user@example.com',
                                    'to'          => ['test@example.com'],
                                    'cc'          => ['cc@example.com'],
                                    'bcc'         => ['bcc@example.com'],
                                ]);

                            return $organization;
                        },
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
