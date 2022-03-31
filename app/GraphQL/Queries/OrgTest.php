<?php declare(strict_types = 1);

namespace App\GraphQL\Queries;

use App\Models\Currency;
use App\Models\Organization;
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
use Tests\WithUser;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Queries\Org
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
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
     */
    public function testInvoke(
        Response $expected,
        mixed $orgFactory,
        mixed $userFactory = null,
        bool $isRootOrganization = false,
        Closure $organizationCallback = null,
    ): void {
        // Prepare
        $org = $this->setOrganization($orgFactory);

        if ($isRootOrganization) {
            $this->setRootOrganization($org);
        }

        $this->setUser($userFactory, $org);

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
                        static function (): Organization {
                            $currency     = Currency::factory()->create([
                                'id'   => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                                'name' => 'currency1',
                                'code' => 'CUR',
                            ]);
                            $organization = Organization::factory()
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
                        false,
                    ],
                    'root' => [
                        new GraphQLSuccess('org', new JsonFragment('root', true)),
                        true,
                    ],
                ]),
            ),
        ]))->getData();
    }
    // </editor-fold>
}
