<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Currency;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use App\Services\DataLoader\Schema\InputTranslationText;
use App\Services\I18n\Eloquent\TranslatedString;
use Closure;
use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLSuccess;
use Tests\GraphQL\GraphQLValidationError;
use Tests\GraphQL\JsonFragmentSchema;
use Tests\TestCase;

use function array_key_exists;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\Update
 */
class UpdateTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     *
     * @param array<string,mixed> $settings
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        array $settings = null,
        Closure $dataFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory);
        $this->setUser($userFactory, $organization);
        $this->setSettings($settings);

        $input = [];
        $data  = [];
        $map   = [];
        $file  = [];

        $hasLogo      = false;
        $hasFavicon   = false;
        $hasWelcome   = false;
        $hasDashboard = false;

        if ($dataFactory) {
            $data  = $dataFactory($this);
            $input = $data;

            if (array_key_exists('branding', $input)) {
                if (isset($input['branding']['logo_url'])) {
                    $map['0']                      = ['variables.input.branding.logo_url'];
                    $file['0']                     = $input['branding']['logo_url'];
                    $input['branding']['logo_url'] = null;
                    $hasLogo                       = true;
                }

                if (isset($input['branding']['favicon_url'])) {
                    $map['1']                         = ['variables.input.branding.favicon_url'];
                    $file['1']                        = $input['branding']['favicon_url'];
                    $input['branding']['favicon_url'] = null;
                    $hasFavicon                       = true;
                }

                if (isset($input['branding']['welcome_image_url'])) {
                    $map['2']                               = ['variables.input.branding.welcome_image_url'];
                    $file['2']                              = $input['branding']['welcome_image_url'];
                    $input['branding']['welcome_image_url'] = null;
                    $hasWelcome                             = true;
                }

                if (isset($input['branding']['dashboard_image_url'])) {
                    $map['2']                                 = ['variables.input.branding.dashboard_image_url'];
                    $file['2']                                = $input['branding']['dashboard_image_url'];
                    $input['branding']['dashboard_image_url'] = null;
                    $hasDashboard                             = true;
                }
            }
        }


        $query = /** @lang GraphQL */
            <<<'GRAPHQL'
            mutation mutate($input: OrgUpdateInput!) {
                org {
                    update(input: $input){
                        result
                        org {
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
                }
            }
            GRAPHQL;

        $operations = [
            'operationName' => 'mutate',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        // Mocks
        $client = Mockery::mock(Client::class);

        $this->app->bind(Client::class, static function () use ($client) {
            return $client;
        });

        if ($expected instanceof GraphQLSuccess) {
            if ($organization->reseller) {
                $client
                    ->shouldReceive('updateBrandingData')
                    ->once()
                    ->andReturn(true);
                $new_logo_url = $hasLogo ? 'https://example.com/logo.png' : null;
                $client
                    ->shouldReceive('updateCompanyLogo')
                    ->once()
                    ->andReturn($new_logo_url);

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
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $organization = $organization->fresh();

            self::assertEquals($data['locale'], $organization->locale);
            self::assertEquals($data['currency_id'], $organization->currency_id);
            self::assertEquals($data['website_url'], $organization->website_url);
            self::assertEquals($data['email'], $organization->email);
            self::assertEquals($data['analytics_code'], $organization->analytics_code);

            if ($organization->reseller) {
                $hasLogo && self::assertEquals('https://example.com/logo.png', $organization->branding_logo_url);
                $hasFavicon && self::assertEquals(
                    'https://example.com/favicon.png',
                    $organization->branding_favicon_url,
                );
                $hasWelcome && self::assertEquals(
                    'https://example.com/imageOnTheRight.png',
                    $organization->branding_welcome_image_url,
                );
            }

            !$hasLogo && self::assertNull($organization->branding_logo_url);
            !$hasFavicon && self::assertNull($organization->branding_favicon_url);
            !$hasWelcome && self::assertNull($organization->branding_welcome_image_url);
            !$hasDashboard && self::assertNull($organization->branding_dashboard_image_url);

            if (array_key_exists('branding', $data)) {
                self::assertEquals($data['branding']['dark_theme'], $organization->branding_dark_theme);
                self::assertEquals($data['branding']['main_color'], $organization->branding_main_color);
                self::assertEquals($data['branding']['secondary_color'], $organization->branding_secondary_color);
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
            new OrganizationDataProvider('org', '439a0a06-d98a-41f0-b8e5-4e5722518e01'),
            new OrganizationUserDataProvider('org', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                               => [
                    new GraphQLSuccess('org', new JsonFragmentSchema('update', self::class)),
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
                'invalid request/Invalid color'    => [
                    new GraphQLValidationError('org'),
                    [],
                    static function (): array {
                        return [
                            'branding' => [
                                'main_color' => 'Color',
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid locale'   => [
                    new GraphQLValidationError('org'),
                    [],
                    static function (): array {
                        return [
                            'locale' => 'en_UKX',
                        ];
                    },
                ],
                'invalid request/Invalid currency' => [
                    new GraphQLValidationError('org'),
                    [],
                    static function (): array {
                        return [
                            'currency_id' => 'wrongId',
                        ];
                    },
                ],
                'invalid request/deleted currency' => [
                    new GraphQLValidationError('org'),
                    [],
                    static function (): array {
                        $currency = Currency::factory()->create([
                            'id' => 'f9834bc1-2f2f-4c57-bb8d-7a224ac24982',
                        ]);
                        $currency->delete();

                        return [
                            'currency_id' => $currency->id,
                        ];
                    },
                ],
                'invalid request/Invalid format'   => [
                    new GraphQLValidationError('org'),
                    [
                        'ep.image.max_size' => 2000,
                        'ep.image.formats'  => ['png'],
                    ],
                    static function (TestCase $test): array {
                        return [
                            'branding' => [
                                'logo_url'    => UploadedFile::fake()->create('branding_logo.jpg', 200),
                                'favicon_url' => UploadedFile::fake()->create('branding_favicon.jpg', 200),
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid size'     => [
                    new GraphQLValidationError('org'),
                    [
                        'ep.image.max_size' => 2000,
                        'ep.image.formats'  => ['png'],
                    ],
                    static function (TestCase $test): array {
                        return [
                            'branding' => [
                                'logo_url'    => UploadedFile::fake()->create('logo.png', 3024),
                                'favicon_url' => UploadedFile::fake()
                                    ->create('favicon.png', 3024),
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid url'      => [
                    new GraphQLValidationError('org'),
                    [],
                    static function (TestCase $test): array {
                        return [
                            'website_url' => 'wrong url',
                        ];
                    },
                ],
                'invalid request/Invalid email'    => [
                    new GraphQLValidationError('org'),
                    [],
                    static function (TestCase $test): array {
                        return [
                            'email' => 'wrong mail',
                        ];
                    },
                ],
                'invalid request/Invalid timezone' => [
                    new GraphQLValidationError('org'),
                    [],
                    static function (TestCase $test): array {
                        return [
                            'timezone' => 'Europe/Unknown',
                        ];
                    },
                ],
                'nullable branding'                => [
                    new GraphQLSuccess('org', new JsonFragmentSchema('update', self::class)),
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
                    new GraphQLSuccess('org', new JsonFragmentSchema('update', self::class)),
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
                    new GraphQLSuccess('org', new JsonFragmentSchema('update', self::class)),
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
