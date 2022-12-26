<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Data\Currency;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Schema\Inputs\InputTranslationText;
use App\Services\I18n\Eloquent\TranslatedString;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\AuthOrgDataProvider;
use Tests\DataProviders\GraphQL\Users\OrgUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\TestCase;
use Tests\WithOrganization;
use Tests\WithSettings;
use Tests\WithUser;

use function array_key_exists;
use function implode;
use function trans;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\Update
 *
 * @phpstan-import-type OrganizationFactory from WithOrganization
 * @phpstan-import-type UserFactory from WithUser
 * @phpstan-import-type SettingsFactory from WithSettings
 */
class UpdateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
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
        Closure $dataFactory = null,
    ): void {
        // Prepare
        $org = $this->setOrganization($orgFactory);

        $this->setUser($userFactory, $org);
        $this->setSettings($settingsFactory);

        $data         = [];
        $input        = [];
        $hasLogo      = false;
        $hasFavicon   = false;
        $hasWelcome   = false;
        $hasDashboard = false;

        if ($dataFactory) {
            $data  = $dataFactory($this);
            $input = $data;

            if (array_key_exists('branding', $input)) {
                $hasLogo      = isset($input['branding']['logo_url']);
                $hasFavicon   = isset($input['branding']['favicon_url']);
                $hasWelcome   = isset($input['branding']['welcome_image_url']);
                $hasDashboard = isset($input['branding']['dashboard_image_url']);
            }
        }

        // Mocks
        $client = Mockery::mock(Client::class);

        $this->app->bind(Client::class, static function () use ($client) {
            return $client;
        });

        if ($expected instanceof GraphQLSuccess) {
            if ($org && $org->company) {
                $client
                    ->shouldReceive('updateBrandingData')
                    ->once()
                    ->andReturn(true);
                $newLogoUrl = $hasLogo ? 'https://example.com/logo.png' : null;
                $client
                    ->shouldReceive('updateCompanyLogo')
                    ->once()
                    ->andReturn($newLogoUrl);

                $newFaviconUrl = $hasFavicon ? 'https://example.com/favicon.png' : null;
                $client
                    ->shouldReceive('updateCompanyFavicon')
                    ->once()
                    ->andReturn($newFaviconUrl);

                $newWelcomeUrl = $hasWelcome ? 'https://example.com/imageOnTheRight.png' : null;
                $client
                    ->shouldReceive('updateCompanyMainImageOnTheRight')
                    ->once()
                    ->andReturn($newWelcomeUrl);
            } else {
                $client
                    ->shouldNotReceive('updateCompanyLogo');
                $client
                    ->shouldNotReceive('updateCompanyFavicon');
                $client
                    ->shouldNotReceive('updateCompanyMainImageOnTheRight');
                $client
                    ->shouldNotReceive('updateBrandingData');
            }
        }

        // Test
        $this
            ->graphQL(
            /** @lang GraphQL */
                <<<'GRAPHQL'
                mutation test($input: OrgUpdateInput!) {
                    org {
                        update(input: $input){
                            result
                            org {
                                id
                                type
                                name
                                email
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
                                    dashboard_image_url
                                    welcome_heading {
                                        locale
                                        text
                                    }
                                    welcome_underline {
                                        locale
                                        text
                                    }
                                }
                            }
                        }
                    }
                }
                GRAPHQL,
                [
                    'input' => $input,
                ],
            )
            ->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            self::assertNotNull($org);

            $org = $org->refresh();

            self::assertEquals($data['locale'], $org->locale);
            self::assertEquals($data['currency_id'], $org->currency_id);
            self::assertEquals($data['website_url'], $org->website_url);
            self::assertEquals($data['email'], $org->email);
            self::assertEquals($data['analytics_code'], $org->analytics_code);

            if ($org->company) {
                $hasLogo && self::assertEquals('https://example.com/logo.png', $org->branding_logo_url);
                $hasFavicon && self::assertEquals(
                    'https://example.com/favicon.png',
                    $org->branding_favicon_url,
                );
                $hasWelcome && self::assertEquals(
                    'https://example.com/imageOnTheRight.png',
                    $org->branding_welcome_image_url,
                );
            }

            !$hasLogo && self::assertNull($org->branding_logo_url);
            !$hasFavicon && self::assertNull($org->branding_favicon_url);
            !$hasWelcome && self::assertNull($org->branding_welcome_image_url);
            !$hasDashboard && self::assertNull($org->branding_dashboard_image_url);

            if (array_key_exists('branding', $data)) {
                self::assertEquals($data['branding']['dark_theme'], $org->branding_dark_theme);
                self::assertEquals($data['branding']['main_color'], $org->branding_main_color);
                self::assertEquals($data['branding']['secondary_color'], $org->branding_secondary_color);
            }
        }
    }

    /**
     * @covers ::getTranslationText
     */
    public function testGetTranslationText(): void {
        $update = new class() extends Update {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            /**
             * @inheritDoc
             */
            public function getTranslationText(?array $translations): ?array {
                return parent::getTranslationText($translations);
            }
        };

        self::assertEquals(
            [
                new InputTranslationText([
                    'language_code' => 'en_GB',
                    'text'          => 'a',
                ]),
                new InputTranslationText([
                    'language_code' => 'en',
                    'text'          => 'a',
                ]),
                new InputTranslationText([
                    'language_code' => 'unknown',
                    'text'          => 'b',
                ]),
            ],
            $update->getTranslationText([
                [
                    'locale' => 'en_GB',
                    'text'   => 'a',
                ],
                [
                    'locale' => 'unknown',
                    'text'   => 'b',
                ],
            ]),
        );
    }

    /**
     * @covers ::getTranslatedString
     */
    public function testGetTranslatedString(): void {
        $update = new class() extends Update {
            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct() {
                // empty
            }

            public function getTranslatedString(?array $translations): ?TranslatedString {
                return parent::getTranslatedString($translations);
            }
        };

        self::assertEquals(
            new TranslatedString([
                'en_GB'   => 'a',
                'unknown' => 'b',
            ]),
            $update->getTranslatedString([
                [
                    'locale' => 'en_GB',
                    'text'   => 'a',
                ],
                [
                    'locale' => 'unknown',
                    'text'   => 'b',
                ],
            ]),
        );
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new AuthOrgDataProvider('org', '439a0a06-d98a-41f0-b8e5-4e5722518e01'),
            new OrgUserDataProvider('org', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                               => [
                    new GraphQLSuccess('org'),
                    [],
                    static function (): array {
                        $currency = Currency::factory()->create();
                        Reseller::factory()->create([
                            'id' => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                        ]);

                        return [
                            'locale'         => 'en',
                            'currency_id'    => $currency->getKey(),
                            'website_url'    => 'https://www.example.com',
                            'email'          => 'test@example.com',
                            'analytics_code' => 'code',
                            'timezone'       => 'Europe/London',
                            'branding'       => [
                                'dark_theme'          => false,
                                'main_color'          => '#ffffff',
                                'secondary_color'     => '#ffffff',
                                'logo_url'            => UploadedFile::fake()->create('branding_logo.jpg', 20),
                                'favicon_url'         => UploadedFile::fake()->create('branding_favicon.jpg', 100),
                                'welcome_image_url'   => UploadedFile::fake()->create('branding_welcome.jpg', 100),
                                'dashboard_image_url' => UploadedFile::fake()->create('branding_dashboard.jpg', 100),
                                'welcome_heading'     => [
                                    [
                                        'locale' => 'en_GB',
                                        'text'   => 'heading',
                                    ],
                                ],
                                'welcome_underline'   => [
                                    [
                                        'locale' => 'en_GB',
                                        'text'   => 'underline',
                                    ],
                                ],
                            ],
                        ];
                    },
                ],
                'invalid input'                    => [
                    new GraphQLValidationError('org', static function (Repository $config): array {
                        return [
                            'input.locale'               => [
                                trans('validation.locale'),
                            ],
                            'input.currency_id'          => [
                                trans('validation.currency_id'),
                            ],
                            'input.website_url'          => [
                                trans('validation.url'),
                            ],
                            'input.email'                => [
                                trans('validation.email'),
                            ],
                            'input.timezone'             => [
                                trans('validation.timezone'),
                            ],
                            'input.branding.main_color'  => [
                                trans('validation.color'),
                            ],
                            'input.branding.logo_url'    => [
                                trans('validation.mimes', [
                                    'values' => implode(', ', $config->get('ep.image.formats')),
                                ]),
                            ],
                            'input.branding.favicon_url' => [
                                trans('validation.mimes', [
                                    'values' => implode(', ', $config->get('ep.image.formats')),
                                ]),
                            ],
                        ];
                    }),
                    [
                        'ep.image.max_size' => 2000,
                        'ep.image.formats'  => ['png'],
                    ],
                    static function (): array {
                        return [
                            'locale'      => 'en_UKX',
                            'timezone'    => 'Europe/Unknown',
                            'email'       => 'wrong mail',
                            'website_url' => 'wrong url',
                            'currency_id' => 'wrongId',
                            'branding'    => [
                                'main_color'  => 'Color',
                                'logo_url'    => UploadedFile::fake()->create('branding_logo.jpg', 200),
                                'favicon_url' => UploadedFile::fake()->create('branding_favicon.jpg', 200),
                            ],
                        ];
                    },
                ],
                'invalid request/deleted currency' => [
                    new GraphQLValidationError('org', static function (): array {
                        return [
                            'input.currency_id' => [
                                trans('validation.currency_id'),
                            ],
                        ];
                    }),
                    [],
                    static function (): array {
                        $currency = Currency::factory()->create([
                            'id'         => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                            'deleted_at' => Date::now(),
                        ]);

                        return [
                            'currency_id' => $currency->getKey(),
                        ];
                    },
                ],
                'invalid request/Invalid size'     => [
                    new GraphQLValidationError('org', static function (Repository $config): array {
                        return [
                            'input.branding.logo_url'    => [
                                trans('validation.max.file', [
                                    'max' => $config->get('ep.image.max_size') ?? 0,
                                ]),
                            ],
                            'input.branding.favicon_url' => [
                                trans('validation.max.file', [
                                    'max' => $config->get('ep.image.max_size') ?? 0,
                                ]),
                            ],
                        ];
                    }),
                    [
                        'ep.image.max_size' => 2000,
                        'ep.image.formats'  => ['png'],
                    ],
                    static function (TestCase $test): array {
                        return [
                            'branding' => [
                                'logo_url'    => UploadedFile::fake()->create('logo.png', 3024),
                                'favicon_url' => UploadedFile::fake()->create('favicon.png', 3024),
                            ],
                        ];
                    },
                ],
                'nullable branding'                => [
                    new GraphQLSuccess('org'),
                    [],
                    static function (): array {
                        $currency = Currency::factory()->create();
                        Reseller::factory()->create([
                            'id' => '439a0a06-d98a-41f0-b8e5-4e5722518e01',
                        ]);

                        return [
                            'locale'         => 'en',
                            'currency_id'    => $currency->getKey(),
                            'website_url'    => 'https://www.example.com',
                            'email'          => 'test@example.com',
                            'analytics_code' => 'analytics_code',
                            'timezone'       => 'Europe/London',
                            'branding'       => [
                                'logo_url'            => null,
                                'dark_theme'          => false,
                                'main_color'          => null,
                                'secondary_color'     => null,
                                'favicon_url'         => null,
                                'welcome_image_url'   => null,
                                'welcome_heading'     => null,
                                'welcome_underline'   => null,
                                'dashboard_image_url' => null,
                            ],
                        ];
                    },
                ],
                'no reseller organization'         => [
                    new GraphQLSuccess('org'),
                    [],
                    static function (): array {
                        $currency = Currency::factory()->create();

                        return [
                            'locale'         => 'en',
                            'currency_id'    => $currency->getKey(),
                            'website_url'    => 'https://www.example.com',
                            'email'          => 'test@example.com',
                            'analytics_code' => 'code',
                            'timezone'       => 'Europe/London',
                            'branding'       => [
                                'dark_theme'        => false,
                                'main_color'        => '#ffffff',
                                'secondary_color'   => '#ffffff',
                                'logo_url'          => UploadedFile::fake()->create('branding_logo.jpg', 20),
                                'favicon_url'       => UploadedFile::fake()->create('branding_favicon.jpg', 100),
                                'welcome_image_url' => UploadedFile::fake()->create('branding_welcome.jpg', 100),
                                'welcome_heading'   => [
                                    [
                                        'locale' => 'en_GB',
                                        'text'   => 'heading',
                                    ],
                                ],
                                'welcome_underline' => [
                                    [
                                        'locale' => 'en_GB',
                                        'text'   => 'underline',
                                    ],
                                ],
                            ],
                        ];
                    },
                ],
                'no reseller organization/null'    => [
                    new GraphQLSuccess('org'),
                    [],
                    static function (): array {
                        $currency = Currency::factory()->create();

                        return [
                            'locale'         => 'en',
                            'currency_id'    => $currency->getKey(),
                            'website_url'    => 'https://www.example.com',
                            'email'          => 'test@example.com',
                            'analytics_code' => 'code',
                            'timezone'       => 'Europe/London',
                            'branding'       => [
                                'dark_theme'        => false,
                                'main_color'        => '#ffffff',
                                'secondary_color'   => '#ffffff',
                                'logo_url'          => null,
                                'favicon_url'       => null,
                                'welcome_image_url' => null,
                                'welcome_heading'   => [
                                    [
                                        'locale' => 'en_GB',
                                        'text'   => 'heading',
                                    ],
                                ],
                                'welcome_underline' => [
                                    [
                                        'locale' => 'en_GB',
                                        'text'   => 'underline',
                                    ],
                                ],
                            ],
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
