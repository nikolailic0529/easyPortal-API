<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
use App\Models\Location;
use App\Models\Organization;
use App\Models\Reseller;
use App\Models\Status;
use Closure;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Tests\DataProviders\GraphQL\Organizations\RootOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;

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
        $this->setUser($userFactory, $this->setOrganization($organizationFactory));

        $this->setSettings($settings);

        $organizationId = 'wrong';
        if ($prepare) {
            $organizationId = $prepare($this)->getKey();
        }

        $client = Http::fake([
            '*' => Http::response(
                [
                    [
                        'id'                         => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                        'username'                   => 'virtualcomputersa_3@tesedi.com',
                        'enabled'                    => true,
                        'emailVerified'              => true,
                        'notBefore'                  => 0,
                        'totp'                       => false,
                        'firstName'                  => 'Reseller',
                        'lastName'                   => 'virtualcomputersa_3',
                        'email'                      => 'virtualcomputersa_3@tesedi.com',
                        'disableableCredentialTypes' => [],
                        'requiredActions'            => [],
                    ],
                ],
                200,
            ),
        ]);
        $this->app->instance(Factory::class, $client);

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
                        status {
                            id
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
                        users {
                            id
                            username
                            firstName
                            lastName
                            email
                            enabled
                            emailVerified
                        }
                        roles {
                            id
                            name
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

        $client = Http::fake([
            '*' => Http::response(
                [
                    [
                        'id'                         => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                        'username'                   => 'virtualcomputersa_3@tesedi.com',
                        'enabled'                    => true,
                        'emailVerified'              => true,
                        'notBefore'                  => 0,
                        'totp'                       => false,
                        'firstName'                  => 'Reseller',
                        'lastName'                   => 'virtualcomputersa_3',
                        'email'                      => 'virtualcomputersa_3@tesedi.com',
                        'disableableCredentialTypes' => [],
                        'requiredActions'            => [],
                        'attributes'                 => [
                            'locale' => [
                                'de',
                            ],
                            'phone'  => [
                                '12345678',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);
        $this->app->instance(Factory::class, $client);

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
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderQuery(): array {
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('organization'),
            new UserDataProvider('organization', [
                'administer',
                'edit-organization',
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
                        'status'         => [
                            'id'   => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                            'name' => 'active',
                        ],
                        'users'          => [
                            [
                                'id'            => '3d000bc3-d7bb-44bd-9d3e-e327a5c32f1a',
                                'username'      => 'virtualcomputersa_3@tesedi.com',
                                'enabled'       => true,
                                'emailVerified' => true,
                                'firstName'     => 'Reseller',
                                'lastName'      => 'virtualcomputersa_3',
                                'email'         => 'virtualcomputersa_3@tesedi.com',
                            ],
                        ],
                        'roles'          => [
                            [
                                'id'   => 'f9396bc1-2f2f-4c58-2f2f-7a224ac20946',
                                'name' => 'role1',
                            ],
                        ],
                    ]),
                    [
                        'ep.headquarter_type' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                    ],
                    static function (): Organization {
                        $currency = Currency::factory()->create([
                            'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                            'name' => 'currency1',
                            'code' => 'CUR',
                        ]);
                        $status   = Status::factory()->create([
                            'id'          => 'f9396bc1-2f2f-4c57-bb8d-7a224ac20949',
                            'name'        => 'active',
                            'key'         => 'active',
                            'object_type' => (new Reseller())->getMorphClass(),
                        ]);
                        $reseller = Reseller::factory()
                            ->for($status)
                            ->hasContacts(1, [
                                'name'        => 'contact1',
                                'email'       => 'contact1@test.com',
                                'phone_valid' => false,
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
        return (new CompositeDataProvider(
            new RootOrganizationDataProvider('organization'),
            new UserDataProvider('organization', [
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
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
