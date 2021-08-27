<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations\Org;

use App\Models\Currency;
use App\Models\Reseller;
use App\Services\DataLoader\Client\Client;
use Closure;
use Illuminate\Http\UploadedFile;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\OrganizationUserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\Org\UpdateOrg
 */
class UpdateOrgTest extends TestCase {
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

        $hasLogo    = false;
        $hasFavicon = false;
        $hasWelcome = false;

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
            }
        }

        $query = /** @lang GraphQL */
            'mutation updateOrg($input: UpdateOrgInput!){
            updateOrg(input: $input){
              result
              organization {
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
                  kpi {
                    assets_total
                    assets_active
                    assets_covered
                    customers_active
                    customers_active_new
                    contracts_active
                    contracts_active_amount
                    contracts_active_new
                    contracts_expiring
                    quotes_active
                    quotes_active_amount
                    quotes_active_new
                    quotes_expiring
                  }
              }
            }
          }';

        $operations = [
            'operationName' => 'updateOrg',
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

                $new_favicon_url = $hasFavicon ? 'https://example.com/favicon.png' : null;
                $client
                    ->shouldReceive('updateCompanyFavicon')
                    ->once()
                    ->andReturn($new_favicon_url);

                $new_welcome_url = $hasWelcome ? 'https://example.com/imageOnTheRight.png' : null;
                $client
                    ->shouldReceive('updateCompanyMainImageOnTheRight')
                    ->once()
                    ->andReturn($new_welcome_url);
            } else {
                $client
                    ->shouldNotReceive('updateCompanyLogo')
                    ->shouldNotReceive('updateCompanyFavicon')
                    ->shouldNotReceive('updateCompanyMainImageOnTheRight')
                    ->shouldNotReceive('updateBrandingData');
            }
        }

        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $organization = $organization->fresh();

            $this->assertEquals($data['locale'], $organization->locale);
            $this->assertEquals($data['currency_id'], $organization->currency_id);
            $this->assertEquals($data['website_url'], $organization->website_url);
            $this->assertEquals($data['email'], $organization->email);
            $this->assertEquals($data['analytics_code'], $organization->analytics_code);

            if ($organization->reseller) {
                $hasLogo && $this->assertEquals('https://example.com/logo.png', $organization->branding_logo_url);
                $hasFavicon && $this->assertEquals(
                    'https://example.com/favicon.png',
                    $organization->branding_favicon_url,
                );
                $hasWelcome && $this->assertEquals(
                    'https://example.com/imageOnTheRight.png',
                    $organization->branding_welcome_image_url,
                );
            }

            !$hasLogo && $this->assertNull($organization->branding_logo_url);
            !$hasFavicon && $this->assertNull($organization->branding_favicon_url);
            !$hasWelcome && $this->assertNull($organization->branding_welcome_image_url);

            if (array_key_exists('branding', $data)) {
                $this->assertEquals($data['branding']['dark_theme'], $organization->branding_dark_theme);
                $this->assertEquals($data['branding']['main_color'], $organization->branding_main_color);
                $this->assertEquals($data['branding']['secondary_color'], $organization->branding_secondary_color);
            }
        }
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<mixed>
     */
    public function dataProviderInvoke(): array {
        return (new CompositeDataProvider(
            new OrganizationDataProvider('updateOrg', '439a0a06-d98a-41f0-b8e5-4e5722518e01'),
            new OrganizationUserDataProvider('updateOrg', [
                'org-administer',
            ]),
            new ArrayDataProvider([
                'ok'                               => [
                    new GraphQLSuccess('updateOrg', UpdateOrg::class),
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
                                'dark_theme'        => false,
                                'main_color'        => '#ffffff',
                                'secondary_color'   => '#ffffff',
                                'logo_url'          => UploadedFile::fake()->create('branding_logo.jpg', 20),
                                'favicon_url'       => UploadedFile::fake()->create('branding_favicon.jpg', 100),
                                'welcome_image_url' => UploadedFile::fake()->create('branding_welcome.jpg', 100),
                                'welcome_heading'   => 'heading',
                                'welcome_underline' => 'underline',
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid color'    => [
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
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
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [],
                    static function (): array {
                        return [
                            'locale' => 'en_UKX',
                        ];
                    },
                ],
                'invalid request/Invalid currency' => [
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [],
                    static function (): array {
                        return [
                            'currency_id' => 'wrongId',
                        ];
                    },
                ],
                'invalid request/deleted currency' => [
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
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
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
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
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
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
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [],
                    static function (TestCase $test): array {
                        return [
                            'website_url' => 'wrong url',
                        ];
                    },
                ],
                'invalid request/Invalid email'    => [
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [],
                    static function (TestCase $test): array {
                        return [
                            'email' => 'wrong mail',
                        ];
                    },
                ],
                'invalid request/Invalid timezone' => [
                    new GraphQLError('updateOrg', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    [],
                    static function (TestCase $test): array {
                        return [
                            'timezone' => 'Europe/Unknown',
                        ];
                    },
                ],
                'nullable branding'                => [
                    new GraphQLSuccess('updateOrg', UpdateOrg::class),
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
                                'logo_url'          => null,
                                'dark_theme'        => false,
                                'main_color'        => null,
                                'secondary_color'   => null,
                                'favicon_url'       => null,
                                'welcome_image_url' => null,
                                'welcome_heading'   => null,
                                'welcome_underline' => null,
                            ],
                        ];
                    },
                ],
                'no reseller organization'         => [
                    new GraphQLSuccess('updateOrg', UpdateOrg::class),
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
                                'welcome_heading'   => 'heading',
                                'welcome_underline' => 'underline',
                            ],
                        ];
                    },
                ],
                'no reseller organization/null'    => [
                    new GraphQLSuccess('updateOrg', UpdateOrg::class),
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
                                'welcome_heading'   => 'heading',
                                'welcome_underline' => 'underline',
                            ],
                        ];
                    },
                ],
            ]),
        ))->getData();
    }
    // </editor-fold>
}
