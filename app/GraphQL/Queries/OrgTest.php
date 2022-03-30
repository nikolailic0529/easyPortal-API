<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
use App\Models\Kpi;
use App\Models\Location;
use App\Models\Organization as ModelsOrganization;
use App\Models\Reseller;
use App\Models\ResellerLocation;
use App\Services\I18n\Eloquent\TranslatedString;
use Closure;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\MergeDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\UnknownValue;
use Tests\DataProviders\GraphQL\Organizations\UnknownOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\UnknownUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\JsonFragment;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Org
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class OrgTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @covers       \App\GraphQL\Queries\Organization::root
     * @covers       \App\GraphQL\Queries\Organization::branding
     *
     * @dataProvider dataProviderInvoke
     *
     * @param OrganizationFactory $orgFactory
     * @param UserFactory         $userFactory
     * @param SettingsFactory     $settingsFactory
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        mixed $settingsFactory = null,
        bool $isRootOrganization = false,
        Closure $organizationCallback = null,
    ): void {
        // Prepare
        $org = $this->setOrganization($orgFactory);

        if ($isRootOrganization) {
            $this->setRootOrganization($org);
        }

        $this->setUser($userFactory, $org);
        $this->setSettings($settingsFactory);

        if ($org && $organizationCallback) {
            $organizationCallback($this, $org);
        }

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
                new UnknownOrgDataProvider(),
                new UnknownUserDataProvider(),
                new ArrayDataProvider([
                    'ok' => [
                        new GraphQLSuccess('org'),
                    ],
                ]),
            ),
            'properties' => new CompositeDataProvider(
                new ArrayDataProvider([
                    'org' => [
                        new UnknownValue(),
                        static function (TestCase $test): ?ModelsOrganization {
                            $currency     = Currency::factory()->create([
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                'name' => 'currency1',
                                'code' => 'CUR',
                            ]);
                            $organization = ModelsOrganization::factory()
                                ->for($currency)
                                ->create([
                                    'id'                               => '439a0a06-d98a-41f0-b8e5-4e5722518e00',
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
                new UnknownUserDataProvider(),
                new ArrayDataProvider([
                    'ok'   => [
                        new GraphQLSuccess('org', [
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
                        ]),
                        [
                            'ep.headquarter_type' => [
                                'f9834bc1-2f2f-4c57-bb8d-7a224ac24985',
                            ],
                        ],
                        false,
                        static function (TestCase $test, ModelsOrganization $organization): void {
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
                                    'id'     => $organization->getKey(),
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

                            $location->resellers()->attach($reseller);
                        },
                    ],
                    'root' => [
                        new GraphQLSuccess('org', new JsonFragment('root', true)),
                        [],
                        true,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
