<?php declare(strict_types = 1);

namespace App\GraphQL\Mutations;

use App\Models\Currency;
use App\Services\DataLoader\Client\Client;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use LastDragon_ru\LaraASP\Testing\Constraints\Response\Response;
use LastDragon_ru\LaraASP\Testing\Providers\ArrayDataProvider;
use LastDragon_ru\LaraASP\Testing\Providers\CompositeDataProvider;
use Mockery;
use Tests\DataProviders\GraphQL\Organizations\OrganizationDataProvider;
use Tests\DataProviders\GraphQL\Users\UserDataProvider;
use Tests\GraphQL\GraphQLError;
use Tests\GraphQL\GraphQLSuccess;
use Tests\TestCase;

use function __;
use function array_key_exists;
use function str_replace;

/**
 * @internal
 * @coversDefaultClass \App\GraphQL\Mutations\UpdateOrganization
 */
class UpdateOrganizationTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    /**
     * @covers ::__invoke
     * @dataProvider dataProviderInvoke
     */
    public function testInvoke(
        Response $expected,
        Closure $organizationFactory,
        Closure $userFactory = null,
        Closure $dataFactory = null,
    ): void {
        // Prepare
        $organization = $this->setOrganization($organizationFactory($this));

        $this->setUser($userFactory, $organization);

        $input = [];
        $data  = [];
        $map   = [];
        $file  = [];

        if ($dataFactory) {
            $data  = $dataFactory($this);
            $input = $data;

            if (array_key_exists('branding', $input)) {
                if (isset($input['branding']['logo_url'])) {
                    $map['0']                      = ['variables.input.branding.logo_url'];
                    $file['0']                     = $input['branding']['logo_url'];
                    $input['branding']['logo_url'] = null;
                }

                if (isset($input['branding']['favicon_url'])) {
                    $map['1']                         = ['variables.input.branding.favicon_url'];
                    $file['1']                        = $input['branding']['favicon_url'];
                    $input['branding']['favicon_url'] = null;
                }

                if (isset($input['branding']['welcome_image_url'])) {
                    $map['2']                               = ['variables.input.branding.welcome_image_url'];
                    $file['2']                              = $input['branding']['welcome_image_url'];
                    $input['branding']['welcome_image_url'] = null;
                }
            }
        }

        $query = /** @lang GraphQL */
            'mutation updateOrganization($input: UpdateOrganizationInput!){
            updateOrganization(input: $input){
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
              }
            }
          }';

        $operations = [
            'operationName' => 'updateOrganization',
            'query'         => $query,
            'variables'     => ['input' => $input],
        ];

        // Fake
        $disc = Storage::fake('public');

        // Mocks
        $client = Mockery::mock(Client::class);

        $this->app->bind(Client::class, static function () use ($client) {
            return $client;
        });

        if ($expected instanceof GraphQLSuccess) {
            $client
                ->shouldReceive('updateBrandingData')
                ->once()
                ->andReturn(true);
            $client
                ->shouldReceive('updateCompanyLogo')
                ->once()
                ->andReturn('https://example.com/logo.png');
        }

        // Test
        $this->multipartGraphQL($operations, $map, $file)->assertThat($expected);

        if ($expected instanceof GraphQLSuccess) {
            $url          = $this->app->make(UrlGenerator::class);
            $prefix       = $url->to($disc->url(''));
            $organization = $organization->fresh();

            $this->assertEquals($data['locale'], $organization->locale);
            $this->assertEquals($data['currency_id'], $organization->currency_id);
            $this->assertEquals($data['website_url'], $organization->website_url);
            $this->assertEquals($data['email'], $organization->email);
            $this->assertEquals($data['analytics_code'], $organization->analytics_code);
            $this->assertEquals('https://example.com/logo.png', $organization->branding_logo_url);

            if (array_key_exists('branding', $data)) {
                $this->assertEquals($data['branding']['dark_theme'], $organization->branding_dark_theme);
                $this->assertEquals($data['branding']['main_color'], $organization->branding_main_color);
                $this->assertEquals($data['branding']['secondary_color'], $organization->branding_secondary_color);

                if (isset($data['branding']['favicon_url'])) {
                    $this->assertNotNull($organization->branding_favicon_url);
                    $disc->assertExists(str_replace($prefix, '', $organization->branding_favicon_url));
                }

                if (isset($data['branding']['welcome_image_url'])) {
                    $this->assertNotNull($organization->branding_welcome_image_url);
                    $disc->assertExists(str_replace($prefix, '', $organization->branding_welcome_image_url));
                }
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
            new OrganizationDataProvider('updateOrganization', '439a0a06-d98a-41f0-b8e5-4e5722518e01'),
            new UserDataProvider('updateOrganization', [
                'edit-organization',
            ]),
            new ArrayDataProvider([
                'ok'                               => [
                    new GraphQLSuccess('updateOrganization', UpdateOrganization::class),
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
                'invalid request/Invalid color'    => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'branding' => [
                                'main_color' => 'Color',
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid locale'   => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'locale' => 'en_UKX',
                        ];
                    },
                ],
                'invalid request/Invalid currency' => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (): array {
                        return [
                            'currency_id' => 'wrongId',
                        ];
                    },
                ],
                'invalid request/deleted currency' => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
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
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        $config  = $test->app->make(Repository::class);
                        $maxSize = 2000;
                        $config->set('ep.image.max_size', $maxSize);
                        $config->set('ep.image.formats', ['png']);

                        return [
                            'branding' => [
                                'logo_url'    => UploadedFile::fake()->create('branding_logo.jpg', 200),
                                'favicon_url' => UploadedFile::fake()->create('branding_favicon.jpg', 200),
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid size'     => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        $config  = $test->app->make(Repository::class);
                        $maxSize = 2000;
                        $config->set('ep.image.max_size', $maxSize);
                        $config->set('ep.image.formats', ['png']);

                        return [
                            'branding' => [
                                'logo_url'    => UploadedFile::fake()->create('logo.png', $maxSize + 1024),
                                'favicon_url' => UploadedFile::fake()
                                    ->create('favicon.jpg', $maxSize + 1024),
                            ],
                        ];
                    },
                ],
                'invalid request/Invalid url'      => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'website_url' => 'wrong url',
                        ];
                    },
                ],
                'invalid request/Invalid email'    => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'email' => 'wrong mail',
                        ];
                    },
                ],
                'invalid request/Invalid timezone' => [
                    new GraphQLError('updateOrganization', static function (): array {
                        return [__('errors.validation_failed')];
                    }),
                    static function (TestCase $test): array {
                        return [
                            'timezone' => 'Europe/Unknown',
                        ];
                    },
                ],
                'nullable branding'                => [
                    new GraphQLSuccess('updateOrganization', UpdateOrganization::class),
                    static function (): array {
                        $currency = Currency::factory()->create();

                        return [
                            'locale'         => 'en',
                            'currency_id'    => $currency->getKey(),
                            'website_url'    => 'https://www.example.com',
                            'email'          => 'test@example.com',
                            'analytics_code' => 'analytics_code',
                            'timezone'       => 'Europe/London',
                            'branding'       => [
                                // Logo cannot be null
                                'logo_url'          => UploadedFile::fake()->create('branding_logo.jpg', 200),
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
            ]),
        ))->getData();
    }
    // </editor-fold>
}
