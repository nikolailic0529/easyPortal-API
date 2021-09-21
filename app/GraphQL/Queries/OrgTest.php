<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
use App\Models\Kpi;
use App\Models\Location;
use App\Models\Organization as ModelsOrganization;
use App\Models\Reseller;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\DataProviders\GraphQL\Organizations\AnyOrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\AnyUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Org
 */
class OrgTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @covers       \App\GraphQL\Queries\Organization::root
     * @covers       \App\GraphQL\Queries\Organization::branding
     *
     * @param array<mixed> $settings
     *
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        bool $isRootOrganization = false,
        array $settings = [],
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);

        $this->setUser($userFactory, $organization);

        if ($isRootOrganization) {
            $this->setRootOrganization($organization);
        }

        $this->setSettings($settings);

        // Test
        $this->graphQL(/** @lang GraphQL */ '{
            org {
                id
                name
                root
                locale
                website_url
                email
                analytics_code
                currency_id
                timezone
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
        }')->assertThat($expected);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new MergeDataProvider([
            'any'        => new CompositeDataProvider(
                new AnyOrganizationDataProvider('org'),
                new AnyUserDataProvider(),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('org', null),
                    ],
                ]),
            ),
            'properties' => new CompositeDataProvider(
                new ArrayDataProvider([
                    'org' => [
                        new UnknownValue(),
                        static function (TestCase $test): ?ModelsOrganization {
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
                            $organization = ModelsOrganization::factory()
                                ->for($currency)
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
                new AnyUserDataProvider(),
                new ArrayDataProvider([
                    'ok'   => [
                        new GraphQLSuccess('org', Org::class, [
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
                        false,
                        [
                            'ep.headquarter_type' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                        ],
                    ],
                    'root' => [
                        new GraphQLSuccess('org', Org::class, new JsonFragment('root', true)),
                        true,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
