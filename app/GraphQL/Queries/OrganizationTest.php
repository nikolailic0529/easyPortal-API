<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\GraphQL\Types\Audit;
use App\Models\Currency;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Reseller;
use App\Models\User as ModelsUser;
use App\Services\KeyCloak\Client\Client;
use App\Services\KeyCloak\Client\Types\Group;
use App\Services\KeyCloak\Client\Types\User;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use Mockery\MockInterface;
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
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $user         = $this->setUser($userFactory, $organization);

        if ($prepare) {
            $prepare($this, $organization, $user);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
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
                            username
                            firstName
                            lastName
                            email
                            enabled
                            emailVerified
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
        Closure $clientFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $this->setUser($userFactory, $organization);
        $this->setSettings([
            'ep.keycloak.client_id' => 'client_id',
        ]);

        if ($prepare) {
            $prepare($this, $organization);
        }

        if ($clientFactory) {
            $this->override(Client::class, $clientFactory);
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
                            permissions
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
     * @coversNothing
     *
     * @dataProvider dataProviderAudits
     *
     * @param array<mixed> $settings
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
                    ]),
                    [
                        'ep.headquarter_type' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                    ],
                    static function (TestCase $test, ?Organization $organization, ?ModelsUser $user): Organization {
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
                                'branding_welcome_heading'         => 'heading',
                                'branding_welcome_underline'       => 'underline',
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
                new RootOrganizationDataProvider('organization'),
                new OrganizationUserDataProvider('organization', [
                    'administer',
                ]),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('organization', self::class, new JsonFragment('users', [
                            [
                                'id'            => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                'username'      => 'virtualcomputersa_3@tesedi.com',
                                'enabled'       => true,
                                'emailVerified' => true,
                                'firstName'     => 'Reseller',
                                'lastName'      => 'virtualcomputersa_3',
                                'email'         => 'virtualcomputersa_3@tesedi.com',
                            ],
                        ])),
                        static function (TestCase $test, Organization $organization): void {
                            $organization->keycloak_group_id = 'f9396bc1-2f2f-4c58-2f2f-7a224ac20945';
                            Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);
                            $organization->save();
                        },
                        static function (MockInterface $mock): void {
                            $mock
                                ->shouldReceive('users')
                                ->once()
                                ->andReturn([
                                    new User([
                                        'id'            => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                        'username'      => 'virtualcomputersa_3@tesedi.com',
                                        'enabled'       => true,
                                        'emailVerified' => true,
                                        'firstName'     => 'Reseller',
                                        'lastName'      => 'virtualcomputersa_3',
                                        'email'         => 'virtualcomputersa_3@tesedi.com',
                                    ]),
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
                        new GraphQLSuccess('organization', self::class, new JsonFragment('users', [
                            [
                                'id'            => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                'username'      => 'virtualcomputersa_3@tesedi.com',
                                'enabled'       => true,
                                'emailVerified' => true,
                                'firstName'     => 'Reseller',
                                'lastName'      => 'virtualcomputersa_3',
                                'email'         => 'virtualcomputersa_3@tesedi.com',
                            ],
                        ])),
                        static function (TestCase $test, Organization $organization): void {
                            $organization->keycloak_group_id = 'f9396bc1-2f2f-4c58-2f2f-7a224ac20945';
                            Reseller::factory()->create([
                                'id' => $organization->getKey(),
                            ]);
                            $organization->save();
                        },
                        static function (MockInterface $mock): void {
                            $mock
                                ->shouldReceive('users')
                                ->once()
                                ->andReturn([
                                    new User([
                                        'id'            => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                        'username'      => 'virtualcomputersa_3@tesedi.com',
                                        'enabled'       => true,
                                        'emailVerified' => true,
                                        'firstName'     => 'Reseller',
                                        'lastName'      => 'virtualcomputersa_3',
                                        'email'         => 'virtualcomputersa_3@tesedi.com',
                                    ]),
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
                                'name'        => 'subgroup1',
                                'permissions' => [
                                    '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                ],
                            ],
                        ])),
                        static function (TestCase $test, Organization $organization): void {
                            if (!$organization->keycloak_group_id) {
                                $organization->keycloak_group_id = $test->faker->uuid();
                                $organization->save();
                            }
                            Permission::factory()->create([
                                'id'  => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                'key' => 'permission1',
                            ]);
                        },
                        static function (MockInterface $mock): void {
                            $mock
                                ->shouldReceive('getGroup')
                                ->once()
                                ->andReturn(
                                    new Group([
                                        'id'        => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1d',
                                        'name'      => 'test',
                                        'subGroups' => [
                                            [
                                                'id'          => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                                'name'        => 'subgroup1',
                                                'clientRoles' => [
                                                    'client_id' => [
                                                        'permission1',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ]),
                                );
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
                                'name'        => 'subgroup1',
                                'permissions' => [
                                    '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                ],
                            ],
                        ])),
                        static function (TestCase $test, Organization $organization): void {
                            if (!$organization->keycloak_group_id) {
                                $organization->keycloak_group_id = $test->faker->uuid();
                                $organization->save();
                            }
                            Permission::factory()->create([
                                'id'  => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1b',
                                'key' => 'permission1',
                            ]);
                        },
                        static function (MockInterface $mock): void {
                            $mock
                                ->shouldReceive('getGroup')
                                ->once()
                                ->andReturn(
                                    new Group([
                                        'id'        => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1d',
                                        'name'      => 'test',
                                        'subGroups' => [
                                            [
                                                'id'          => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                                'name'        => 'subgroup1',
                                                'clientRoles' => [
                                                    'client_id' => [
                                                        'permission1',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ]),
                                );
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
                                            'email'    => 'test@gmail.com',
                                            'password' => 'pass',
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
                            $user         = ModelsUser::factory()->create([
                                'id' => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                            ]);
                            $organization = Organization::factory()
                                ->hasAudits(1, [
                                    'id'          => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20947',
                                    'object_type' => $user->getMorphClass(),
                                    'object_id'   => $user->getKey(),
                                    'user_id'     => '439a0a06-d98a-41f0-b8e5-4e5722518e02',
                                    'context'     => ['email' => 'test@gmail.com', 'password' => 'pass'],
                                    'action'      => 'action1',
                                    'created_at'  => '2021-01-01 00:00:00',
                                ])
                                ->create([
                                    'id' => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                ]);

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
                                        'context'         => json_encode(['email' => 'test@gmail.com']),
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
                            $user         = ModelsUser::factory()->create([
                                'id'       => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                                'password' => 'pass',
                            ]);
                            $organization = Organization::factory()
                                ->hasAudits(1, [
                                    'id'          => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20948',
                                    'object_type' => $user->getMorphClass(),
                                    'object_id'   => $user->getKey(),
                                    'user_id'     => '439a0a06-d98a-41f0-b8e5-4e5722518e02',
                                    'context'     => ['email' => 'test@gmail.com'],
                                    'action'      => 'action1',
                                    'created_at'  => '2021-01-01 00:00:00',
                                ])
                                ->create([
                                    'id' => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
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
